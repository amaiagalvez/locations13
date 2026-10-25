<?php

namespace Locations13\Tests\Feature\Communities;

use App\Models\User;
use Locations13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CommunityUniqueIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_name_cannot_be_repeated_among_the_communities_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        Community::factory()->create(['name' => 'Gorbeialdea']);

        $this->from(route('communities.index'))->post(route('communities.store'), ['name' => 'Gorbeialdea'])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Community::query()->where('name', 'Gorbeialdea')->count());
    }

    public function test_an_archived_community_still_keeps_its_name(): void
    {
        $this->actingAs($this->user());
        Community::factory()->archived()->create(['name' => 'Gorbeialdea']);

        $this->from(route('communities.index'))->post(route('communities.store'), ['name' => 'Gorbeialdea'])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Community::query()->withTrashed()->count());
    }

    public function test_a_name_in_the_trash_may_be_reused(): void
    {
        $this->actingAs($this->user());
        $trashed = Community::factory()->create(['name' => 'Gorbeialdea']);
        $trashed->delete();

        // The conflict modal offers the creation with the trashed name; the button posts the
        // hidden reuse flag the store validates.
        $this->from(route('communities.index'))->post(route('communities.store'), [
            'name' => 'Gorbeialdea',
            'reuse_deleted_name' => '1',
        ])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Community::query()->whereNull('deleted_at')->count());
    }

    public function test_updating_a_community_to_a_taken_name_is_rejected(): void
    {
        $this->actingAs($this->user());
        Community::factory()->create(['name' => 'Gorbeialdea']);
        $other = Community::factory()->create(['name' => 'Arabako Errioxa']);

        $this->from(route('communities.index'))->put(route('communities.update', $other), ['name' => 'Gorbeialdea'])
            ->assertRedirect(route('communities.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame('Arabako Errioxa', $other->refresh()->name);
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
