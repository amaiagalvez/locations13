<?php

namespace Locations13\Tests\Feature\Districts;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DistrictTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('districts.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_districts_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        District::factory()->create(['name' => 'Goierri']);
        District::factory()->trashed()->create(['name' => 'Tolosaldea']);

        $this->get(route('districts.index'))
            ->assertOk()
            ->assertSee('Goierri')
            ->assertDontSee('Tolosaldea');

        $this->get(route('districts.trash.index'))
            ->assertOk()
            ->assertSee('Tolosaldea')
            ->assertDontSee('Goierri');
    }

    public function test_a_trashed_district_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $district = District::factory()->trashed()->create();

        $this->patch(route('districts.trash.restore', $district->id))
            ->assertRedirect(route('districts.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($district);
    }

    public function test_a_trashed_district_cannot_be_restored_while_a_live_district_uses_its_name(): void
    {
        $this->actingAs($this->user());
        $district = District::factory()->trashed()->create(['name' => 'Goierri']);
        District::factory()->create(['name' => 'Goierri']);

        $this->patch(route('districts.trash.restore', $district->id))
            ->assertRedirect(route('districts.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($district);
    }

    public function test_a_trashed_district_without_towns_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $district = District::factory()->trashed()->create();

        $this->delete(route('districts.trash.destroy', $district->id))
            ->assertRedirect(route('districts.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('LOC_districts', ['id' => $district->id]);
    }

    public function test_a_district_with_a_town_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $district = District::factory()->create();
        Town::factory()->create(['district_id' => $district->id]);

        $this->delete(route('districts.destroy', $district))
            ->assertRedirect(route('districts.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($district);
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
