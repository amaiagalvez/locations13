<?php

namespace Locations13\Tests\Feature\Districts;

use App\Models\User;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DistrictSelectOptionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_options_offer_the_active_districts_matching_the_term(): void
    {
        $this->actingAs($this->user());
        $matched = District::factory()->create(['name' => 'Goierri']);
        District::factory()->create(['name' => 'Tolosaldea']);
        District::factory()->archived()->create(['name' => 'Goierrialdea']);

        $this->get(route('districts.options', ['q' => 'Goi']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $matched->id)
            ->assertJsonPath('results.0.text', 'Goierri');
    }

    public function test_a_user_without_the_districts_permission_is_rejected(): void
    {
        $user = User::create([
            'name' => 'No district access',
            'email' => 'no-option-district@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('districts.options'))
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
