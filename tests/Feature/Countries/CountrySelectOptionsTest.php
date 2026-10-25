<?php

namespace Locations13\Tests\Feature\Countries;

use App\Models\User;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CountrySelectOptionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_options_offer_the_active_countries_matching_the_term(): void
    {
        $this->actingAs($this->user());
        $matched = Country::factory()->create(['name' => 'Euskal Herria']);
        Country::factory()->create(['name' => 'Estatu espainiarra']);
        Country::factory()->archived()->create(['name' => 'Euskaldunia']);

        $this->get(route('countries.options', ['q' => 'Eusk']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $matched->id)
            ->assertJsonPath('results.0.text', 'Euskal Herria');
    }

    public function test_the_options_without_a_term_offer_the_first_page_of_active_countries(): void
    {
        $this->actingAs($this->user());
        $first = Country::factory()->create(['name' => 'Akkara']);
        Country::factory()->create(['name' => 'Zabala']);

        $this->get(route('countries.options'))
            ->assertOk()
            ->assertJsonPath('results.0.id', $first->id)
            ->assertJsonCount(2, 'results');
    }

    public function test_a_user_without_the_countries_permission_is_rejected(): void
    {
        $user = User::create([
            'name' => 'No country access',
            'email' => 'no-option@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('countries.options'))
            ->assertForbidden();
    }

    private function user(): User
    {
        return $this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
    }
}
