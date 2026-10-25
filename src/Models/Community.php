<?php

namespace Locations13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Locations13\Policies\CommunityPolicy;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Locations13\Database\Factories\CommunityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $ine_code
 * @property int|null $state_id
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read State|null $state
 */
#[Fillable(['name', 'ine_code', 'notes', 'state_id'])]
#[UsePolicy(CommunityPolicy::class)]
#[UseFactory(CommunityFactory::class)]
class Community extends Model
{
    protected $table = 'LOC_communities';

    /** @use HasFactory<CommunityFactory> */
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
     * The community's state (EAE, Nafarroa, IEH belong to a state or stand alone). A state a
     * community points at cannot be in the trash: the state's own delete guard refuses it.
     *
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * The herrialdeak that name this community. The delete guard of the country reads the child
     * tables, so a deleted country still blocks dropping the community.
     *
     * @return HasMany<Country, $this>
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    /**
     * A community a herrialde points at cannot be dropped, not even when that country is in the
     * trash. The permanent delete is refused by the foreign keys; this hook covers the soft
     * delete, and takes the row lock in a transaction of its own that TransactionalDelete keeps
     * held until the delete is written.
     */
    protected static function booted(): void
    {
        static::deleting(static function (self $community): bool {
            $locked = DB::transaction(static fn (): self => self::withTrashed()
                ->whereKey($community->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            return ! self::hasChildren($locked->getKey());
        });
    }

    public static function hasChildren(int $communityId): bool
    {
        return DB::table('LOC_countries')->where('community_id', $communityId)->exists();
    }
}
