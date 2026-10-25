<?php

namespace Locations13\Database\Factories;

use Locations13\Models\State;
use Locations13\Models\Community;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Community>
 */
class CommunityFactory extends Factory
{
    use HasStates;

    protected $model = Community::class;

    /**
     * The state is created for the community when the caller gives none. Use `->for($state)` when
     * a test pins the parent, and `->orphan()` for one without any.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'ine_code' => null,
            'state_id' => static::getRecordOrNew(State::class)->id,
        ];
    }

    /** Communities may exist without a state. */
    public function orphan(): static
    {
        return $this->state(['state_id' => null]);
    }
}
