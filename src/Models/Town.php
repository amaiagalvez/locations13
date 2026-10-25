<?php

namespace Locations13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Locations13\Policies\TownPolicy;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Locations13\Database\Factories\TownFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $ine_code
 * @property int|null $district_id
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read District|null $district
 * @property-read int $postal_codes_count
 */
#[Fillable(['name', 'ine_code', 'notes', 'district_id'])]
#[UsePolicy(TownPolicy::class)]
#[UseFactory(TownFactory::class)]
class Town extends Model
{
    protected $table = 'LOC_towns';

    /** @use HasFactory<TownFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * @var array{active: bool}
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * @return array{active: 'boolean'}
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * The town's comarca, when it names one.
     *
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * The neighbourhoods inside this town. The delete guard queries the child tables instead,
     * because a town may not be dropped while a deleted neighbourhood still points at it.
     *
     * @return HasMany<Neighbourhood, $this>
     */
    public function neighbourhoods(): HasMany
    {
        return $this->hasMany(Neighbourhood::class);
    }

    /**
     * The postal codes of this town. They are not rows the application reads through this relation
     * to guard deletions: dropping a town takes its codes with it instead of being refused.
     *
     * @return HasMany<PostalCode, $this>
     */
    public function postalCodes(): HasMany
    {
        return $this->hasMany(PostalCode::class);
    }

    /**
     * A town a record points at cannot be dropped, not even when that record is in the trash. The
     * permanent delete is refused by the foreign keys; this hook covers the soft delete, and takes
     * the row lock in a transaction of its own that TransactionalDelete keeps held until the
     * delete is written.
     */
    protected static function booted(): void
    {
        // A postal code only exists as part of its town: it goes with it into the trash, and comes
        // back with it. The soft delete takes them away once the guard above has accepted the
        // delete; nothing else touches a code, so no listener order is ever involved.
        static::deleting(static function (self $town): bool {
            $locked = DB::transaction(static fn (): self => self::withTrashed()
                ->whereKey($town->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            if (self::hasChildren($locked->getKey())) {
                return false;
            }

            $town->postalCodes()->delete();

            return true;
        });

        // The permanent delete takes every code row with it, the trashed ones too, so the town
        // foreign key (which is restrict) never refuses to drop the town because of a code.
        static::forceDeleting(static function (self $town): void {
            $town->postalCodes()->withTrashed()->get()->each->forceDelete();
        });

        static::restored(static function (self $town): void {
            $town->postalCodes()->onlyTrashed()->get()->each->restore();
        });
    }

    /**
     * The postal codes do not block: they are taken away with the town, by their foreign key.
     */
    public static function hasChildren(int $townId): bool
    {
        return DB::table('LOC_neighbourhoods')->where('town_id', $townId)->exists();
    }
}
