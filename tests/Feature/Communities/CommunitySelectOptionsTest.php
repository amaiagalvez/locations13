<?php

namespace Locations13\Tests\Feature\Communities;

use App\Models\User;
use Locations13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CommunitySelectOptionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_options_offer_the_active_communities_matching_the_term(): void
    {
        $this->actingAs($this->user());
        $matched = Community::factory()->create(['name' => 'Euskadi']);
        Community::factory()->create(['name' => 'Nafarroa']);
        Community::factory()->archived()->create(['name' => 'Euskadiko Erkidegoa']);

        $this->get(route('communities.options', ['q' => 'Euska']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $matched->id)
            ->assertJsonPath('results.0.text', 'Euskadi');
    }

    public function test_the_options_without_a_term_offer_the_first_page_of_active_communities(): void
    {
        $this->actingAs($this->user());
        $first = Community::factory()->create(['name' => 'Akkara']);
        Community::factory()->create(['name' => 'Zabala']);

        $this->get(route('communities.options'))
            ->assertOk()
            ->assertJsonPath('results.0.id', $first->id)
            ->assertJsonCount(2, 'results');
    }

    public function test_a_user_without_the_communities_permission_is_rejected(): void
    {
        $user = User::create([
            'name' => 'No community access',
            'email' => 'no-option-community@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('communities.options'))
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
