<?php

namespace Locations13\Tests\Feature\Permissions;

use App\Models\User;
use Basics13\Models\Role;
use Basics13\Models\Permission;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class LocationAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_each_listing_needs_its_own_view_permission(): void
    {
        $this->actingAs($this->holding(['countries.view']));

        $this->get(route('countries.index'))->assertOk();
        $this->get(route('states.index'))->assertForbidden();
        $this->get(route('communities.index'))->assertForbidden();
        $this->get(route('districts.index'))->assertForbidden();
        $this->get(route('towns.index'))->assertForbidden();
        $this->get(route('neighbourhoods.index'))->assertForbidden();
    }

    public function test_archived_lists_and_options_need_the_view_permission(): void
    {
        $this->actingAs($this->holding(['countries.view']));

        $this->get(route('countries.archived.index'))->assertOk();
        $this->get(route('countries.trash.index'))->assertOk();
        $this->getJson(route('countries.options'))->assertOk();

        $this->get(route('states.archived.index'))->assertForbidden();
        $this->getJson(route('states.options'))->assertForbidden();
    }

    public function test_a_read_only_user_cannot_edit_a_country(): void
    {
        $country = Country::factory()->create(['name' => 'Original name']);
        $this->actingAs($this->holding(['countries.view']));

        $this->put(route('countries.update', $country), ['name' => 'Changed name'])->assertForbidden();

        $this->assertSame('Original name', $country->refresh()->name);
    }

    public function test_emptying_the_trash_needs_force_delete(): void
    {
        $country = Country::factory()->trashed()->create();
        $this->actingAs($this->holding(['countries.view', 'countries.delete', 'countries.restore']));

        $this->delete(route('countries.trash.destroy', $country->id))->assertForbidden();

        $this->assertSoftDeleted($country);
    }

    public function test_admin_keeps_managing_the_six_location_resources(): void
    {
        $this->actingAs($this->admin(User::create([
            'name' => 'Admin user',
            'email' => 'admin-locations@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));

        $this->get(route('states.index'))->assertOk();
        $this->get(route('communities.index'))->assertOk();
        $this->get(route('countries.index'))->assertOk();
        $this->get(route('districts.index'))->assertOk();
        $this->get(route('towns.index'))->assertOk();
        $this->get(route('neighbourhoods.index'))->assertOk();
    }

    public function test_every_module_seeds_its_full_set_of_permissions(): void
    {
        foreach (['states', 'communities', 'countries', 'districts', 'towns', 'neighbourhoods'] as $module) {
            $this->assertSame(
                count(Permission::ACTIONS),
                Permission::query()->where('module', $module)->count(),
            );
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function holding(array $permissions): User
    {
        $user = User::create([
            'name' => 'Limited user',
            'email' => 'limited-locations@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $user->assignRole(Role::factory()->withPermissions($permissions)->create());

        return $user;
    }
}
