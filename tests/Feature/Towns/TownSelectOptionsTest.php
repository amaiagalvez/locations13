<?php

namespace Locations13\Tests\Feature\Towns;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class TownSelectOptionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_options_offer_the_active_towns_matching_the_term(): void
    {
        $this->actingAs($this->user());
        $matched = Town::factory()->create(['name' => 'Urretxu']);
        Town::factory()->create(['name' => 'Donostia']);
        Town::factory()->archived()->create(['name' => 'Urretxualdea']);

        $this->get(route('towns.options', ['q' => 'Urre']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $matched->id)
            ->assertJsonPath('results.0.text', 'Urretxu');
    }

    public function test_a_user_without_the_towns_permission_is_rejected(): void
    {
        $user = User::create([
            'name' => 'No town access',
            'email' => 'no-option-town@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('towns.options'))
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
