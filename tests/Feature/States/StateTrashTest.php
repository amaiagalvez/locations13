<?php

namespace Locations13\Tests\Feature\States;

use App\Models\User;
use Locations13\Models\State;
use Locations13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class StateTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('states.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_states_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        State::factory()->create(['name' => 'Euskal Herria']);
        State::factory()->trashed()->create(['name' => 'Estatu espainiarra']);

        $this->get(route('states.index'))
            ->assertOk()
            ->assertSee('Euskal Herria')
            ->assertDontSee('Estatu espainiarra');

        $this->get(route('states.trash.index'))
            ->assertOk()
            ->assertSee('Estatu espainiarra')
            ->assertDontSee('Euskal Herria');
    }

    public function test_a_trashed_state_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->trashed()->create();

        $this->patch(route('states.trash.restore', $state->id))
            ->assertRedirect(route('states.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($state);
    }

    public function test_a_trashed_state_cannot_be_restored_while_a_live_state_uses_its_name(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->trashed()->create(['name' => 'Euskal Herria']);
        State::factory()->create(['name' => 'Euskal Herria']);

        $this->patch(route('states.trash.restore', $state->id))
            ->assertRedirect(route('states.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($state);
    }

    public function test_a_trashed_state_without_communities_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->trashed()->create();

        $this->delete(route('states.trash.destroy', $state->id))
            ->assertRedirect(route('states.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('LOC_states', ['id' => $state->id]);
    }

    public function test_a_trashed_state_with_a_community_cannot_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->trashed()->create();
        Community::factory()->trashed()->create(['state_id' => $state->id]);

        $this->delete(route('states.trash.destroy', $state->id))
            ->assertRedirect(route('states.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));

        $this->assertDatabaseHas('LOC_states', ['id' => $state->id]);
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
