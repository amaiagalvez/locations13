<?php

namespace Locations13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Locations13\Policies\DistrictPolicy;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Locations13\Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * A `district` is a comarca (Goierri, Tolosaldea, Lizarraldea) that MAY hang from a country.
 *
 * @property int $id
 * @property string $name
 * @property string|null $ine_code
 * @property int|null $country_id
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Country|null $country
 */
#[Fillable(['name', 'ine_code', 'notes', 'country_id'])]
#[UsePolicy(DistrictPolicy::class)]
#[UseFactory(DistrictFactory::class)]
class District extends Model
{
    protected $table = 'LOC_districts';

    /** @use HasFactory<DistrictFactory> */
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
     * The comarca's herrialde, when it names one.
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The towns that name this comarca. The delete guard queries the child tables instead,
     * because a comarca may not be dropped while a deleted town still points at it.
     *
     * @return HasMany<Town, $this>
     */
    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }

    /**
     * A comarca a town points at cannot be dropped, not even when that town is in the trash. The
     * permanent delete is refused by the foreign keys; this hook covers the soft delete, and takes
     * the row lock in a transaction of its own that TransactionalDelete keeps held until the
     * delete is written.
     */
    protected static function booted(): void
    {
        static::deleting(static function (self $district): bool {
            $locked = DB::transaction(static fn (): self => self::withTrashed()
                ->whereKey($district->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            return ! self::hasChildren($locked->getKey());
        });
    }

    public static function hasChildren(int $districtId): bool
    {
        return DB::table('LOC_towns')->where('district_id', $districtId)->exists();
    }
}
