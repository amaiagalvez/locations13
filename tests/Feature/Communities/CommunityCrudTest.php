<?php

namespace Locations13\Tests\Feature\Communities;

use App\Models\User;
use Locations13\Models\State;
use Locations13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CommunityCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('communities.index'))->assertRedirect(route('login'));
        $this->post(route('communities.store'), ['name' => 'Guest community'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('LOC_communities', 0);
    }

    public function test_users_without_the_communities_permission_receive_a_403(): void
    {
        $user = User::create([
            'name' => 'No community access',
            'email' => 'no-access-community@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $this->get(route('communities.index'))->assertForbidden();
        $this->post(route('communities.store'), ['name' => 'Forbidden community'])->assertForbidden();
        $this->assertDatabaseCount('LOC_communities', 0);
    }

    public function test_an_admin_can_create_update_and_delete_a_community(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->create();

        $this->post(route('communities.store'), [
            'name' => 'EAE',
            'state_id' => (string) $state->id,
        ])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $community = Community::query()->sole();
        $this->assertSame($state->id, $community->state_id);

        $this->get(route('communities.index'))
            ->assertOk()
            ->assertSee('community-create')
            ->assertSee('community-edit-'.$community->id)
            ->assertSee('EAE');

        $this->from(route('communities.index'))->put(route('communities.update', $community), [
            'name' => 'EAE berria',
        ])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertSame('EAE berria', $community->refresh()->name);

        $this->from(route('communities.index'))->delete(route('communities.destroy', $community))
            ->assertRedirect(route('communities.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($community);
    }

    public function test_a_community_without_a_state_is_accepted(): void
    {
        $this->actingAs($this->user());

        $this->post(route('communities.store'), ['name' => 'IEH'])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHasNoErrors();

        $community = Community::query()->sole();
        $this->assertNull($community->state_id);

        // The list shows it with the state empty.
        $this->get(route('communities.index'))->assertOk();
    }

    public function test_an_edit_that_removes_the_state_of_a_community(): void
    {
        $this->actingAs($this->user());
        $state = State::factory()->create();
        $community = Community::factory()->create(['state_id' => $state->id]);

        $this->from(route('communities.index'))->put(route('communities.update', $community), [
            'name' => 'EAE',
            'state_id' => '',
        ])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull($community->refresh()->state_id);
    }

    public function test_a_state_that_is_archived_is_not_offered_but_the_assigned_one_keeps_working(): void
    {
        $this->actingAs($this->user());
        $archived = State::factory()->archived()->create();
        $community = Community::factory()->create(['state_id' => $archived->id]);

        // Editing without changing the state keeps the archived one.
        $this->from(route('communities.index'))->put(route('communities.update', $community), [
            'name' => 'EAE',
            'state_id' => (string) $archived->id,
        ])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHasNoErrors();
        $this->assertSame($archived->id, $community->refresh()->state_id);

        // Choosing it for a new community is refused: it is not active.
        $this->from(route('communities.index'))->post(route('communities.store'), [
            'name' => 'Froga berria',
            'state_id' => (string) $archived->id,
        ])->assertSessionHasErrors('state_id');
        $this->assertDatabaseCount('LOC_communities', 1);
    }

    public function test_a_state_that_does_not_exist_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->post(route('communities.store'), ['name' => 'Froga herria', 'state_id' => 999999])
            ->assertSessionHasErrors('state_id');

        $this->assertDatabaseCount('LOC_communities', 0);
    }

    public function test_the_list_search_filters_by_name(): void
    {
        $this->actingAs($this->user());
        Community::factory()->create(['name' => 'EAE']);
        Community::factory()->create(['name' => 'Nafarroa']);

        $this->get(route('communities.index', ['search' => 'EAE']))
            ->assertOk()
            ->assertSee('EAE')
            ->assertDontSee('>Nafarroa<', false);
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
