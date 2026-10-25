<?php

namespace Locations13\Database\Factories;

use Locations13\Models\Town;
use Locations13\Models\PostalCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostalCode>
 */
class PostalCodeFactory extends Factory
{
    protected $model = PostalCode::class;

    /**
     * The town is set by the caller (`->for($town)`): a code without its town is not valid, and
     * the column is NOT NULL.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('#####'),
        ];
    }
}
