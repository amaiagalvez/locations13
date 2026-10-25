<?php

namespace Locations13\Tests\Feature\Seeders;

use Locations13\Models\Town;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Locations13\Models\Neighbourhood;
use Locations13\Database\Seeders\DevelopLocationSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DevelopLocationSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_seeds_a_coherent_tree_with_postal_codes_and_neighbourhoods(): void
    {
        app(DevelopLocationSeeder::class)->run(10);

        $this->assertDatabaseHas('LOC_states', ['name' => 'Euskal Herria']);
        $this->assertDatabaseHas('LOC_communities', ['name' => 'EAE']);
        $this->assertDatabaseHas('LOC_countries', ['name' => 'Gipuzkoa']);
        $this->assertDatabaseHas('LOC_districts', ['name' => 'Goierri']);

        $urretxu = Town::query()->where('name', 'Urretxu')->firstOrFail();
        $this->assertSame('Goierri', $urretxu->district?->name);
        $this->assertSame(['20700'], $urretxu->postalCodes()->pluck('code')->all());
        $this->assertSame(2, Neighbourhood::query()->where('town_id', $urretxu->id)->count());
    }

    public function test_it_seeds_the_expected_number_of_towns_and_their_rows(): void
    {
        app(DevelopLocationSeeder::class)->run(10);

        // Urretxu and Donostia plus the synthetic ones.
        $this->assertSame(12, Town::query()->count());
        $this->assertSame(12, Town::query()->has('postalCodes')->count());
        $this->assertSame(12, Neighbourhood::query()->count());
        $this->assertSame(3, Country::query()->count());
        $this->assertDatabaseHas('LOC_towns', ['name' => 'Develop Town 010']);
    }
}
