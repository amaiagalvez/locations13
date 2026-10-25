<?php

namespace Locations13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Locations13\Policies\StatePolicy;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Locations13\Database\Factories\StateFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * The top of the tree: a state is the whole (`Euskal Herria`) and has no parents.
 *
 * @property int $id
 * @property string $name
 * @property string|null $ine_code
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['name', 'ine_code', 'notes'])]
#[UsePolicy(StatePolicy::class)]
#[UseFactory(StateFactory::class)]
class State extends Model
{
    protected $table = 'LOC_states';

    /** @use HasFactory<StateFactory> */
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
     * The communities that name this state. The delete guard queries the child tables instead,
     * because a state may not be dropped while a deleted community still points at it.
     *
     * @return HasMany<Community, $this>
     */
    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
    }

    /**
     * A state a community points at cannot be dropped, not even when that community is in the
     * trash. The permanent delete is refused by the foreign keys; this hook covers the soft
     * delete, and takes the row lock in a transaction of its own that TransactionalDelete keeps
     * held until the delete is written.
     */
    protected static function booted(): void
    {
        static::deleting(static function (self $state): bool {
            $locked = DB::transaction(static fn (): self => self::withTrashed()
                ->whereKey($state->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            return ! self::hasChildren($locked->getKey());
        });
    }

    /**
     * The lookup visits the table, not the relations, so the deleted children keep blocking too.
     */
    public static function hasChildren(int $stateId): bool
    {
        return DB::table('LOC_communities')->where('state_id', $stateId)->exists();
    }
}
