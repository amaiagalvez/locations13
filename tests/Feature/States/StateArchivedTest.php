<?php

namespace Locations13\Tests\Feature\States;

use App\Models\User;
use Locations13\Models\State;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class StateArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_states_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        State::factory()->archived()->create(['name' => 'Euskalertza']);
        State::factory()->create(['name' => 'Euskal Herria']);

        $this->get(route('states.index'))
            ->assertOk()
            ->assertSee('Euskal Herria')
            ->assertDontSee('Euskalertza');

        $this->get(route('states.archived.index'))
            ->assertOk()
            ->assertSee('Euskalertza')
            ->assertDontSee('Euskal Herria');
    }

    public function test_archived_states_are_searchable_by_name(): void
    {
        $this->actingAs($this->user());
        State::factory()->archived()->create(['name' => 'Euskalertza']);
        State::factory()->archived()->create(['name' => 'Lapurdi']);

        $this->get(route('states.archived.index', ['search' => 'Lapu']))
            ->assertOk()
            ->assertSee('Lapurdi')
            ->assertDontSee('Euskalertza');
    }

    public function test_a_state_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->create();

        $this->patch(route('states.archive', $state))
            ->assertRedirect(route('states.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($state->refresh()->active);

        $this->patch(route('states.archived.activate', $state))
            ->assertRedirect(route('states.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($state->refresh()->active);
    }

    public function test_the_unique_name_index_keeps_archived_and_new_states_apart(): void
    {
        $this->actingAs($this->user());
        State::factory()->archived()->create(['name' => 'Euskalertza']);

        $this->from(route('states.index'))->post(route('states.store'), ['name' => 'Euskalertza'])
            ->assertRedirect(route('states.index'))
            ->assertSessionHasErrors('name');
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
