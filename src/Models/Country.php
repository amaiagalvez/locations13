<?php

namespace Locations13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Locations13\Policies\CountryPolicy;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Locations13\Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * In this package a `country` is a herrialde (Araba, Bizkaia, Gipuzkoa, Lapurdi, Nafarroa,
 * Zuberoa): it MAY hang from a community and MAY stand alone.
 *
 * @property int $id
 * @property string $name
 * @property string|null $ine_code
 * @property int|null $community_id
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Community|null $community
 */
#[Fillable(['name', 'ine_code', 'notes', 'community_id'])]
#[UsePolicy(CountryPolicy::class)]
#[UseFactory(CountryFactory::class)]
class Country extends Model
{
    protected $table = 'LOC_countries';

    /** @use HasFactory<CountryFactory> */
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
     * The herrialde's community, when it names one.
     *
     * @return BelongsTo<Community, $this>
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * The comarcas that name this herrialde. The delete guard queries the child tables instead,
     * because a herrialde may not be dropped while a deleted district still points at it.
     *
     * @return HasMany<District, $this>
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * A herrialde a record of the lower levels points at cannot be dropped, not even when that
     * child is in the trash. The permanent delete is refused by the foreign keys; this hook covers
     * the soft delete, and takes the row lock in a transaction of its own that TransactionalDelete
     * keeps held until the delete is written.
     */
    protected static function booted(): void
    {
        static::deleting(static function (self $country): bool {
            $locked = DB::transaction(static fn (): self => self::withTrashed()
                ->whereKey($country->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            return ! self::hasChildren($locked->getKey());
        });
    }

    public static function hasChildren(int $countryId): bool
    {
        return DB::table('LOC_districts')->where('country_id', $countryId)->exists();
    }
}
