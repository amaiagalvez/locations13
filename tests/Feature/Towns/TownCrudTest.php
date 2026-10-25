<?php

namespace Locations13\Tests\Feature\Towns;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class TownCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('towns.index'))->assertRedirect(route('login'));
        $this->post(route('towns.store'), ['name' => 'Guest town'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('LOC_towns', 0);
    }

    public function test_users_without_the_towns_permission_receive_a_403(): void
    {
        $user = User::create([
            'name' => 'No town access',
            'email' => 'no-access-town@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $this->get(route('towns.index'))->assertForbidden();
        $this->post(route('towns.store'), ['name' => 'Forbidden town'])->assertForbidden();
        $this->assertDatabaseCount('LOC_towns', 0);
    }

    public function test_an_admin_can_create_update_and_delete_a_town(): void
    {
        $this->actingAs($this->user());
        $district = District::factory()->create(['name' => 'Goierri']);

        $this->from(route('towns.index'))->post(route('towns.store'), [
            'name' => 'Urretxu',
            'ine_code' => '20058',
            'district_id' => (string) $district->id,
        ])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $town = Town::query()->sole();
        $this->assertSame('Urretxu', $town->name);
        $this->assertSame($district->id, $town->district_id);

        $this->get(route('towns.index'))
            ->assertOk()
            ->assertSee('town-create')
            ->assertSee('town-edit-'.$town->id)
            ->assertSee('Urretxu')
            ->assertSee('Goierri');

        $this->from(route('towns.index'))->put(route('towns.update', $town), [
            'name' => 'Urretxu berria',
            'ine_code' => '',
        ])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('LOC_towns', [
            'id' => $town->id,
            'name' => 'Urretxu berria',
            'ine_code' => null,
        ]);

        $this->delete(route('towns.destroy', $town))
            ->assertRedirect(route('towns.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($town);
    }

    public function test_a_town_without_a_district_is_accepted(): void
    {
        $this->actingAs($this->user());

        $this->from(route('towns.index'))->post(route('towns.store'), ['name' => 'Andosilla'])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull(Town::query()->sole()->district_id);
    }

    public function test_a_town_is_saved_with_the_postal_codes_of_its_form(): void
    {
        $this->actingAs($this->user());

        $this->from(route('towns.index'))->post(route('towns.store'), [
            'name' => 'Donostia',
            // The form textarea sends one code per line.
            'postal_codes' => "20001\n20002\n20001\n  \n",
        ])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasNoErrors();

        $town = Town::query()->sole();
        $this->assertSame(['20001', '20002'], $town->postalCodes()->orderBy('code')->pluck('code')->all());

        $this->get(route('towns.index'))
            ->assertOk()
            ->assertSee('20001, 20002');
    }

    public function test_an_edit_replaces_the_postal_codes_the_form_no_longer_holds(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create(['name' => 'Urretxu']);
        $town->postalCodes()->createMany([['code' => '20700'], ['code' => '20701']]);

        $this->from(route('towns.index'))->put(route('towns.update', $town), [
            'name' => 'Urretxu',
            'postal_codes' => ['20700'],
        ])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(['20700'], $town->postalCodes()->pluck('code')->all());
    }

    public function test_an_edit_without_postal_codes_keeps_the_ones_the_town_has(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create(['name' => 'Urretxu']);
        $town->postalCodes()->create(['code' => '20700']);

        $this->from(route('towns.index'))->put(route('towns.update', $town), ['name' => 'Urretxu berria'])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(['20700'], $town->postalCodes()->pluck('code')->all());
    }

    public function test_an_empty_postal_code_list_removes_them_all(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $town->postalCodes()->create(['code' => '20700']);

        $this->from(route('towns.index'))->put(route('towns.update', $town), [
            'name' => $town->name,
            'postal_codes' => [],
        ])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $town->postalCodes()->count());
    }

    public function test_the_list_search_filters_by_name(): void
    {
        $this->actingAs($this->user());
        Town::factory()->create(['name' => 'Urretxu']);
        Town::factory()->create(['name' => 'Donostia']);

        $this->get(route('towns.index', ['search' => 'Urre']))
            ->assertOk()
            ->assertSee('Urretxu')
            ->assertDontSee('Donostia');

        $this->get(route('towns.index', ['search' => 'Dono']))
            ->assertOk()
            ->assertDontSee('Urretxu')
            ->assertSee('Donostia');
    }

    public function test_the_name_is_trimmed_and_a_short_one_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('towns.index'))->post(route('towns.store'), ['name' => '  Urretxu  '])
            ->assertRedirect(route('towns.index'));
        $this->assertDatabaseHas('LOC_towns', ['name' => 'Urretxu']);

        $this->from(route('towns.index'))->post(route('towns.store'), ['name' => 'A'])
            ->assertSessionHasErrors('name');
    }

    public function test_a_district_that_does_not_exist_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('towns.index'))->post(route('towns.store'), [
            'name' => 'Urretxu',
            'district_id' => 999999,
        ])->assertSessionHasErrors('district_id');

        $this->assertDatabaseCount('LOC_towns', 0);
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
