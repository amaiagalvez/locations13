<?php

namespace Locations13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Locations13\Policies\NeighbourhoodPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Locations13\Database\Factories\NeighbourhoodFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $ine_code
 * @property int $town_id
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Town $town
 */
#[Fillable(['name', 'ine_code', 'notes', 'town_id'])]
#[UsePolicy(NeighbourhoodPolicy::class)]
#[UseFactory(NeighbourhoodFactory::class)]
class Neighbourhood extends Model
{
    protected $table = 'LOC_neighbourhoods';

    /** @use HasFactory<NeighbourhoodFactory> */
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
     * The neighbourhood's town. A town a neighbourhood points at cannot be in the trash: the
     * town's own delete guard refuses the delete while any neighbourhood hangs from it.
     *
     * @return BelongsTo<Town, $this>
     */
    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }
}
