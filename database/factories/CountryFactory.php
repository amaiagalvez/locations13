<?php

namespace Locations13\Database\Factories;

use Locations13\Models\Country;
use Locations13\Models\Community;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    use HasStates;

    protected $model = Country::class;

    /**
     * The community is created for the herrialde when the caller gives none. Use `->for($community)`
     * when a test pins the parent, and `->orphan()` for one without any.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'ine_code' => null,
            'community_id' => static::getRecordOrNew(Community::class)->id,
        ];
    }

    /** Countries (herrialdeak) may exist without a community. */
    public function orphan(): static
    {
        return $this->state(['community_id' => null]);
    }
}
