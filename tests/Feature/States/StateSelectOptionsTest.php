<?php

namespace Locations13\Tests\Feature\States;

use App\Models\User;
use Locations13\Models\State;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class StateSelectOptionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_options_offer_the_active_states_matching_the_term(): void
    {
        $this->actingAs($this->user());
        $matched = State::factory()->create(['name' => 'Espainia']);
        State::factory()->create(['name' => 'Frantzia']);
        State::factory()->archived()->create(['name' => 'Espainiako Erresuma']);

        $this->get(route('states.options', ['q' => 'Espa']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $matched->id)
            ->assertJsonPath('results.0.text', 'Espainia');
    }

    public function test_the_options_without_a_term_offer_the_first_page_of_active_states(): void
    {
        $this->actingAs($this->user());
        $first = State::factory()->create(['name' => 'Akkara']);
        State::factory()->create(['name' => 'Zabala']);

        $this->get(route('states.options'))
            ->assertOk()
            ->assertJsonPath('results.0.id', $first->id)
            ->assertJsonCount(2, 'results');
    }

    public function test_a_user_without_the_states_permission_is_rejected(): void
    {
        $user = User::create([
            'name' => 'No state access',
            'email' => 'no-option-state@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('states.options'))
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
