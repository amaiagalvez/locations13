<?php

namespace Locations13\Database\Factories;

use Locations13\Models\Town;
use Locations13\Models\Neighbourhood;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Neighbourhood>
 */
class NeighbourhoodFactory extends Factory
{
    use HasStates;

    protected $model = Neighbourhood::class;

    /**
     * The town is created for the neighbourhood when the caller gives none: the town is the one
     * parent a neighbourhood always has. Use `->for($town)` when a test pins it, and
     * `->forTown($town, 'Same name')` to place names next to each other inside one town.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'ine_code' => null,
            'town_id' => static::getRecordOrNew(Town::class)->id,
        ];
    }
}
