<?php

namespace Locations13\Tests\Feature\States;

use App\Models\User;
use Locations13\Models\State;
use Locations13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class StateCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('states.index'))->assertRedirect(route('login'));
        $this->post(route('states.store'), ['name' => 'Guest state'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('LOC_states', 0);
    }

    public function test_users_without_the_states_permission_receive_a_403(): void
    {
        $user = User::create([
            'name' => 'No state access',
            'email' => 'no-access-state@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $this->get(route('states.index'))->assertForbidden();
        $this->post(route('states.store'), ['name' => 'Forbidden state'])->assertForbidden();
        $this->assertDatabaseCount('LOC_states', 0);
    }

    public function test_an_admin_can_create_update_and_delete_a_state(): void
    {
        $this->actingAs($this->user());

        $this->from(route('states.index'))->post(route('states.store'), [
            'name' => 'Euskal Herria',
            'ine_code' => 'EH',
        ])
            ->assertRedirect(route('states.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $state = State::query()->sole();
        $this->assertSame('Euskal Herria', $state->name);
        $this->assertSame('EH', $state->ine_code);

        $this->get(route('states.index'))
            ->assertOk()
            ->assertSee('state-create')
            ->assertSee('state-edit-'.$state->id)
            ->assertSee('Euskal Herria')
            ->assertSee('EH');

        $this->from(route('states.index'))->put(route('states.update', $state), [
            'name' => 'Euskal Herria berria',
            'ine_code' => '',
        ])
            ->assertRedirect(route('states.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('LOC_states', [
            'id' => $state->id,
            'name' => 'Euskal Herria berria',
            'ine_code' => null,
        ]);

        $this->delete(route('states.destroy', $state))
            ->assertRedirect(route('states.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($state);
    }

    public function test_a_state_is_created_without_optional_fields(): void
    {
        $this->actingAs($this->user());

        $this->from(route('states.index'))->post(route('states.store'), ['name' => 'Euskal Herria'])
            ->assertRedirect(route('states.index'));

        $this->assertDatabaseHas('LOC_states', ['name' => 'Euskal Herria', 'ine_code' => null]);
    }

    public function test_a_state_with_a_live_community_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->create();
        Community::factory()->create(['state_id' => $state->id]);

        $this->delete(route('states.destroy', $state))
            ->assertRedirect(route('states.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($state);
    }

    public function test_a_state_with_a_trashed_community_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->create();
        Community::factory()->trashed()->create(['state_id' => $state->id]);

        $this->delete(route('states.destroy', $state))
            ->assertRedirect(route('states.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($state);
    }

    public function test_the_list_search_filters_by_name(): void
    {
        $this->actingAs($this->user());
        State::factory()->create(['name' => 'Euskal Herria']);
        State::factory()->create(['name' => 'Estatu espainiarra']);

        $this->get(route('states.index', ['search' => 'Euskal']))
            ->assertOk()
            ->assertSee('Euskal Herria')
            ->assertDontSee('Estatu espainiarra');

        $this->get(route('states.index', ['search' => 'espainiar']))
            ->assertOk()
            ->assertDontSee('Euskal Herria')
            ->assertSee('Estatu espainiarra');
    }

    public function test_the_name_is_trimmed_and_a_short_one_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('states.index'))->post(route('states.store'), ['name' => '  Euskal Herria  '])
            ->assertRedirect(route('states.index'));
        $this->assertDatabaseHas('LOC_states', ['name' => 'Euskal Herria']);

        $this->from(route('states.index'))->post(route('states.store'), ['name' => 'A'])
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
