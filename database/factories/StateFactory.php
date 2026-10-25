<?php

namespace Locations13\Database\Factories;

use Locations13\Models\State;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<State>
 */
class StateFactory extends Factory
{
    use HasStates;

    protected $model = State::class;

    /**
     * A state is the top of the tree, so the definition has no parents: only the fields the form
     * holds, with the name free among the states outside the trash.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'ine_code' => null,
        ];
    }
}
