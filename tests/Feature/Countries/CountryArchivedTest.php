<?php

namespace Locations13\Tests\Feature\Countries;

use App\Models\User;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CountryArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_countries_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Country::factory()->archived()->create(['name' => 'Euskalertza herria']);
        Country::factory()->create(['name' => 'Euskal Herria']);

        $this->get(route('countries.index'))
            ->assertOk()
            ->assertSee('Euskal Herria')
            ->assertDontSee('Euskalertza herria');

        $this->get(route('countries.archived.index'))
            ->assertOk()
            ->assertSee('Euskalertza herria')
            ->assertDontSee('Euskal Herria');
    }

    public function test_archived_countries_are_searchable_by_name(): void
    {
        $this->actingAs($this->user());
        Country::factory()->archived()->create(['name' => 'Euskalertza']);
        Country::factory()->archived()->create(['name' => 'Lapurdi']);

        $this->get(route('countries.archived.index', ['search' => 'Lapu']))
            ->assertOk()
            ->assertSee('Lapurdi')
            ->assertDontSee('Euskalertza');
    }

    public function test_a_country_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->create();

        $this->patch(route('countries.archive', $country))
            ->assertRedirect(route('countries.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($country->refresh()->active);

        $this->patch(route('countries.archived.activate', $country))
            ->assertRedirect(route('countries.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($country->refresh()->active);
    }

    public function test_the_unique_name_index_keeps_archived_and_new_countries_apart(): void
    {
        $this->actingAs($this->user());
        Country::factory()->archived()->create(['name' => 'Euskalertza']);

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Euskalertza'])
            ->assertRedirect(route('countries.index'))
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
