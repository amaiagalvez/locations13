<?php

namespace Locations13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Locations13\Database\Factories\PostalCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * A postal code is a row of its town: no listing, no form, no route and no policy of its own. A
 * row only exists while the town does, and belongs to exactly one town; the town's model hooks
 * take the rows away with it and bring them back when the town is restored.
 *
 * @property int $id
 * @property int $town_id
 * @property string $code
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
#[Fillable(['town_id', 'code', 'notes'])]
#[UseFactory(PostalCodeFactory::class)]
class PostalCode extends Model
{
    protected $table = 'LOC_postal_codes';

    /** @use HasFactory<PostalCodeFactory> */
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
     * @return BelongsTo<Town, $this>
     */
    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }
}
