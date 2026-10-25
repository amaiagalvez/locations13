<?php

namespace Locations13\Tests\Feature\Communities;

use App\Models\User;
use Locations13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CommunityTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('communities.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_communities_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Community::factory()->create(['name' => 'Gorbeialdea']);
        Community::factory()->trashed()->create(['name' => 'Arabako Errioxa']);

        $this->get(route('communities.index'))
            ->assertOk()
            ->assertSee('Gorbeialdea')
            ->assertDontSee('Arabako Errioxa');

        $this->get(route('communities.trash.index'))
            ->assertOk()
            ->assertSee('Arabako Errioxa')
            ->assertDontSee('Gorbeialdea');
    }

    public function test_a_trashed_community_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $community = Community::factory()->trashed()->create();

        $this->patch(route('communities.trash.restore', $community->id))
            ->assertRedirect(route('communities.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($community);
    }

    public function test_a_trashed_community_cannot_be_restored_while_a_live_community_uses_its_name(): void
    {
        $this->actingAs($this->user());
        $community = Community::factory()->trashed()->create(['name' => 'Gorbeialdea']);
        Community::factory()->create(['name' => 'Gorbeialdea']);

        $this->patch(route('communities.trash.restore', $community->id))
            ->assertRedirect(route('communities.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($community);
    }

    public function test_a_trashed_community_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $community = Community::factory()->trashed()->create();

        $this->delete(route('communities.trash.destroy', $community->id))
            ->assertRedirect(route('communities.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('LOC_communities', ['id' => $community->id]);
    }

    public function test_restoring_a_trashed_archived_community_preserves_its_archived_state(): void
    {
        $this->actingAs($this->user());
        $community = Community::factory()->archived()->trashed()->create();

        $this->patch(route('communities.trash.restore', $community->id))
            ->assertRedirect(route('communities.trash.index'));

        $this->assertNotSoftDeleted($community);
        $this->assertFalse($community->refresh()->active);
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
