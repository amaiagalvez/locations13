<?php

namespace Locations13\Tests\Feature\Districts;

use App\Models\User;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DistrictCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('districts.index'))->assertRedirect(route('login'));
        $this->post(route('districts.store'), ['name' => 'Guest district'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('LOC_districts', 0);
    }

    public function test_users_without_the_districts_permission_receive_a_403(): void
    {
        $user = User::create([
            'name' => 'No district access',
            'email' => 'no-access-district@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $this->get(route('districts.index'))->assertForbidden();
        $this->post(route('districts.store'), ['name' => 'Forbidden district'])->assertForbidden();
        $this->assertDatabaseCount('LOC_districts', 0);
    }

    public function test_an_admin_can_create_update_and_delete_a_district(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->create(['name' => 'Gipuzkoa']);

        $this->from(route('districts.index'))->post(route('districts.store'), [
            'name' => 'Goierri',
            'ine_code' => '2005',
            'country_id' => (string) $country->id,
        ])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $district = District::query()->sole();
        $this->assertSame('Goierri', $district->name);
        $this->assertSame($country->id, $district->country_id);

        $this->get(route('districts.index'))
            ->assertOk()
            ->assertSee('district-create')
            ->assertSee('district-edit-'.$district->id)
            ->assertSee('Goierri')
            ->assertSee('Gipuzkoa');

        $this->from(route('districts.index'))->put(route('districts.update', $district), [
            'name' => 'Goierri berria',
            'ine_code' => '',
        ])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('LOC_districts', [
            'id' => $district->id,
            'name' => 'Goierri berria',
            'ine_code' => null,
        ]);

        $this->delete(route('districts.destroy', $district))
            ->assertRedirect(route('districts.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($district);
    }

    public function test_a_district_without_a_country_is_accepted(): void
    {
        $this->actingAs($this->user());

        $this->from(route('districts.index'))->post(route('districts.store'), ['name' => 'Lizarraldea'])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull(District::query()->sole()->country_id);
    }

    public function test_an_edit_that_removes_the_country_of_a_district(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->create();
        $district = District::factory()->create(['country_id' => $country->id]);

        $this->from(route('districts.index'))->put(route('districts.update', $district), [
            'name' => $district->name,
            'country_id' => '',
        ])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull($district->refresh()->country_id);
    }

    public function test_a_country_that_is_archived_is_not_offered_but_the_assigned_one_keeps_working(): void
    {
        $this->actingAs($this->user());
        $archived = Country::factory()->archived()->create();
        $district = District::factory()->create(['country_id' => $archived->id]);

        $this->from(route('districts.index'))->put(route('districts.update', $district), [
            'name' => $district->name,
            'country_id' => (string) $archived->id,
        ])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasNoErrors();
        $this->assertSame($archived->id, $district->refresh()->country_id);

        $this->from(route('districts.index'))->post(route('districts.store'), [
            'name' => 'Beste eskualdea',
            'country_id' => (string) $archived->id,
        ])->assertSessionHasErrors('country_id');
    }

    public function test_a_country_that_does_not_exist_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('districts.index'))->post(route('districts.store'), [
            'name' => 'Eskualdea',
            'country_id' => 999999,
        ])->assertSessionHasErrors('country_id');

        $this->assertDatabaseCount('LOC_districts', 0);
    }

    public function test_the_list_search_filters_by_name(): void
    {
        $this->actingAs($this->user());
        District::factory()->create(['name' => 'Goierri']);
        District::factory()->create(['name' => 'Tolosaldea']);

        $this->get(route('districts.index', ['search' => 'Goi']))
            ->assertOk()
            ->assertSee('Goierri')
            ->assertDontSee('Tolosaldea');

        $this->get(route('districts.index', ['search' => 'Tolo']))
            ->assertOk()
            ->assertDontSee('Goierri')
            ->assertSee('Tolosaldea');
    }

    public function test_the_name_is_trimmed_and_a_short_one_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('districts.index'))->post(route('districts.store'), ['name' => '  Goierri  '])
            ->assertRedirect(route('districts.index'));
        $this->assertDatabaseHas('LOC_districts', ['name' => 'Goierri']);

        $this->from(route('districts.index'))->post(route('districts.store'), ['name' => 'A'])
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
