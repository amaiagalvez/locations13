<?php

namespace Locations13\Tests\Feature\Districts;

use App\Models\User;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DistrictArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_districts_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        District::factory()->archived()->create(['name' => 'Lizarraldea']);
        District::factory()->create(['name' => 'Goierri']);

        $this->get(route('districts.index'))
            ->assertOk()
            ->assertSee('Goierri')
            ->assertDontSee('Lizarraldea');

        $this->get(route('districts.archived.index'))
            ->assertOk()
            ->assertSee('Lizarraldea')
            ->assertDontSee('Goierri');
    }

    public function test_archived_districts_are_searchable_by_name(): void
    {
        $this->actingAs($this->user());
        District::factory()->archived()->create(['name' => 'Lizarraldea']);
        District::factory()->archived()->create(['name' => 'Tolosaldea']);

        $this->get(route('districts.archived.index', ['search' => 'Lizar']))
            ->assertOk()
            ->assertSee('Lizarraldea')
            ->assertDontSee('Tolosaldea');
    }

    public function test_a_district_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $district = District::factory()->create();

        $this->patch(route('districts.archive', $district))
            ->assertRedirect(route('districts.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($district->refresh()->active);

        $this->patch(route('districts.archived.activate', $district))
            ->assertRedirect(route('districts.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($district->refresh()->active);
    }

    public function test_the_unique_name_index_keeps_archived_and_new_districts_apart(): void
    {
        $this->actingAs($this->user());
        District::factory()->archived()->create(['name' => 'Lizarraldea']);

        $this->from(route('districts.index'))->post(route('districts.store'), ['name' => 'Lizarraldea'])
            ->assertRedirect(route('districts.index'))
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
