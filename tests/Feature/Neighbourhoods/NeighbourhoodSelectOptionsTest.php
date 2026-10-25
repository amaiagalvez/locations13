<?php

namespace Locations13\Tests\Feature\Neighbourhoods;

use App\Models\User;
use Locations13\Tests\TestCase;
use Locations13\Models\Neighbourhood;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class NeighbourhoodSelectOptionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_options_offer_the_active_neighbourhoods_matching_the_term(): void
    {
        $this->actingAs($this->user());
        $matched = Neighbourhood::factory()->create(['name' => 'Alde Zaharra']);
        Neighbourhood::factory()->create(['name' => 'Gros']);
        Neighbourhood::factory()->archived()->create(['name' => 'Alde Zaharreko Auzoa']);

        $this->get(route('neighbourhoods.options', ['q' => 'Alde']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $matched->id)
            ->assertJsonPath('results.0.text', 'Alde Zaharra');
    }

    public function test_the_options_without_a_term_offer_the_first_page_of_active_neighbourhoods(): void
    {
        $this->actingAs($this->user());
        $first = Neighbourhood::factory()->create(['name' => 'Akkara']);
        Neighbourhood::factory()->create(['name' => 'Zabala']);

        $this->get(route('neighbourhoods.options'))
            ->assertOk()
            ->assertJsonPath('results.0.id', $first->id)
            ->assertJsonCount(2, 'results');
    }

    public function test_a_user_without_the_neighbourhoods_permission_is_rejected(): void
    {
        $user = User::create([
            'name' => 'No neighbourhood access',
            'email' => 'no-option-neighbourhood@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('neighbourhoods.options'))
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
