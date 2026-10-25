<?php

namespace Locations13\Tests\Feature\Countries;

use App\Models\User;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CountryTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('countries.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_countries_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Country::factory()->create(['name' => 'Euskal Herria']);
        Country::factory()->trashed()->create(['name' => 'Estatu espainiarra']);

        $this->get(route('countries.index'))
            ->assertOk()
            ->assertSee('Euskal Herria')
            ->assertDontSee('Estatu espainiarra');

        $this->get(route('countries.trash.index'))
            ->assertOk()
            ->assertSee('Estatu espainiarra')
            ->assertDontSee('Euskal Herria');
    }

    public function test_a_trashed_country_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->trashed()->create();

        $this->patch(route('countries.trash.restore', $country->id))
            ->assertRedirect(route('countries.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($country);
    }

    public function test_a_trashed_archived_country_keeps_its_archived_state_after_the_restore(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->archived()->trashed()->create();

        $this->patch(route('countries.trash.restore', $country->id))
            ->assertRedirect(route('countries.trash.index'));

        $this->assertNotSoftDeleted($country);
        $this->assertFalse($country->refresh()->active);
    }

    public function test_a_trashed_country_cannot_be_restored_while_a_live_country_uses_its_name(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->trashed()->create(['name' => 'Euskal Herria']);
        Country::factory()->create(['name' => 'Euskal Herria']);

        $this->patch(route('countries.trash.restore', $country->id))
            ->assertRedirect(route('countries.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($country);
    }

    public function test_a_trashed_country_without_children_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->trashed()->create();

        $this->delete(route('countries.trash.destroy', $country->id))
            ->assertRedirect(route('countries.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('LOC_countries', ['id' => $country->id]);
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
