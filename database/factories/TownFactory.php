<?php

namespace Locations13\Database\Factories;

use Locations13\Models\Town;
use Locations13\Models\District;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Town>
 */
class TownFactory extends Factory
{
    use HasStates;

    protected $model = Town::class;

    /**
     * The comarca is created for the town when the caller gives none. Use `->for($district)` when
     * a test pins the parent, and `->orphan()` for one without any.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'ine_code' => null,
            'district_id' => static::getRecordOrNew(District::class)->id,
        ];
    }

    /** Towns may exist without a district. */
    public function orphan(): static
    {
        return $this->state(['district_id' => null]);
    }
}
