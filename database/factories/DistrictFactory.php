<?php

namespace Locations13\Database\Factories;

use Locations13\Models\Country;
use Locations13\Models\District;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    use HasStates;

    protected $model = District::class;

    /**
     * The herrialde is created for the comarca when the caller gives none. Use `->for($country)`
     * when a test pins the parent, and `->orphan()` for one without any.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'ine_code' => null,
            'country_id' => static::getRecordOrNew(Country::class)->id,
        ];
    }

    /** Districts (comarcas) may exist without a country. */
    public function orphan(): static
    {
        return $this->state(['country_id' => null]);
    }
}
