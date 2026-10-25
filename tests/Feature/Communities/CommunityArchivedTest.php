<?php

namespace Locations13\Tests\Feature\Communities;

use App\Models\User;
use Locations13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CommunityArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_communities_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Community::factory()->archived()->create(['name' => 'Ibarrakidea']);
        Community::factory()->create(['name' => 'Gorbeialdea']);

        $this->get(route('communities.index'))
            ->assertOk()
            ->assertDontSee('Ibarrakidea')
            ->assertSee('Gorbeialdea');

        $this->get(route('communities.archived.index'))
            ->assertOk()
            ->assertSee('Ibarrakidea')
            ->assertDontSee('Gorbeialdea');
    }

    public function test_a_community_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $community = Community::factory()->create();

        $this->patch(route('communities.archive', $community))
            ->assertRedirect(route('communities.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($community->refresh()->active);

        $this->patch(route('communities.archived.activate', $community))
            ->assertRedirect(route('communities.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($community->refresh()->active);
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
