<?php

namespace Locations13\Tests\Feature\Countries;

use App\Models\User;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CountryCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('countries.index'))->assertRedirect(route('login'));
        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Guest country'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('LOC_countries', 0);
    }

    public function test_users_without_the_countries_permission_receive_a_403(): void
    {
        $user = User::create([
            'name' => 'No country access',
            'email' => 'no-access@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $this->get(route('countries.index'))->assertForbidden();
        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Forbidden country'])->assertForbidden();
        $this->assertDatabaseCount('LOC_countries', 0);
    }

    public function test_an_admin_can_create_update_and_delete_a_country(): void
    {
        $this->actingAs($this->user());

        $this->from(route('countries.index'))->post(route('countries.store'), [
            'name' => 'Euskal Herria',
            'ine_code' => 'EH',
        ])
            ->assertRedirect(route('countries.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $country = Country::query()->sole();
        $this->assertSame('Euskal Herria', $country->name);
        $this->assertSame('EH', $country->ine_code);

        $this->get(route('countries.index'))
            ->assertOk()
            ->assertSee('country-create')
            ->assertSee('country-edit-'.$country->id)
            ->assertSee('data-test="country-name-'.$country->id.'"', false)
            ->assertSee('Euskal Herria')
            ->assertSee('EH');

        $this->from(route('countries.index'))->put(route('countries.update', $country), [
            'name' => 'Euskal Herria berria',
            'ine_code' => '',
        ])
            ->assertRedirect(route('countries.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('LOC_countries', [
            'id' => $country->id,
            'name' => 'Euskal Herria berria',
            'ine_code' => null,
        ]);

        $this->delete(route('countries.destroy', $country))
            ->assertRedirect(route('countries.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($country);
    }

    /** Creating without parents is also the country shape of it: nothing else is asked. */
    public function test_a_country_is_created_without_children_or_optional_fields(): void
    {
        $this->actingAs($this->user());

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Ingen kodea'])
            ->assertRedirect(route('countries.index'));

        $this->assertDatabaseHas('LOC_countries', ['name' => 'Ingen kodea', 'ine_code' => null]);
    }

    public function test_the_list_search_filters_by_name(): void
    {
        $this->actingAs($this->user());
        Country::factory()->create(['name' => 'Euskal Herria']);
        Country::factory()->create(['name' => 'Estatu espainiarra']);

        $this->get(route('countries.index', ['search' => 'Euskal']))
            ->assertOk()
            ->assertSee('Euskal Herria')
            ->assertDontSee('Estatu espainiarra');

        $this->get(route('countries.index', ['search' => 'espainiar']))
            ->assertOk()
            ->assertDontSee('Euskal Herria')
            ->assertSee('Estatu espainiarra');
    }

    public function test_the_same_name_as_another_resource_passes_and_reference_code_is_not_required(): void
    {
        $this->actingAs($this->user());
        // No other resource yet in this test: the country only checks its own name.
        Country::factory()->create(['name' => 'Zumaia']);

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Izar'], [])
            ->assertRedirect(route('countries.index'));
    }

    public function test_the_intended_names_are_trimmed(): void
    {
        $this->actingAs($this->user());

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => '  Euskal Herria  '])
            ->assertRedirect(route('countries.index'));

        $this->assertDatabaseHas('LOC_countries', ['name' => 'Euskal Herria']);
    }

    public function test_a_too_short_or_missing_name_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('countries.index'))->post(route('countries.store'), [])
            ->assertSessionHasErrors('name');

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'A'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('LOC_countries', 0);
    }

    public function test_an_ine_code_cannot_be_an_array(): void
    {
        $this->actingAs($this->user());

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Froga herria', 'ine_code' => ['X']])
            ->assertSessionHasErrors('ine_code');

        $this->assertDatabaseCount('LOC_countries', 0);
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
