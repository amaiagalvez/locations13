<?php

namespace Locations13\Tests\Feature\Districts;

use App\Models\User;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DistrictUniqueIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_name_cannot_be_repeated_among_the_districts_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        District::factory()->create(['name' => 'Goierri']);

        $this->from(route('districts.index'))->post(route('districts.store'), ['name' => 'Goierri'])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, District::query()->where('name', 'Goierri')->count());
    }

    public function test_a_name_in_the_trash_may_be_reused(): void
    {
        $this->actingAs($this->user());
        District::factory()->create(['name' => 'Goierri'])->delete();

        $this->from(route('districts.index'))->post(route('districts.store'), [
            'name' => 'Goierri',
            'reuse_deleted_name' => '1',
        ])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, District::query()->whereNull('deleted_at')->count());
    }

    public function test_updating_a_district_to_a_taken_name_is_rejected(): void
    {
        $this->actingAs($this->user());
        District::factory()->create(['name' => 'Goierri']);
        $other = District::factory()->create(['name' => 'Tolosaldea']);

        $this->from(route('districts.index'))->put(route('districts.update', $other), ['name' => 'Goierri'])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame('Tolosaldea', $other->refresh()->name);
    }

    public function test_the_same_name_as_another_resource_passes(): void
    {
        $this->actingAs($this->user());
        Country::factory()->create(['name' => 'Zumaia']);

        $this->from(route('districts.index'))->post(route('districts.store'), ['name' => 'Zumaia'])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasNoErrors();
    }

    public function test_an_ine_code_cannot_repeat_among_the_districts_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        District::factory()->create(['name' => 'Goierri', 'ine_code' => '2005']);

        $this->from(route('districts.index'))->post(route('districts.store'), ['name' => 'Tolosaldea', 'ine_code' => '2005'])
            ->assertRedirect(route('districts.index'))
            ->assertSessionHasErrors('ine_code');

        $this->assertSame(1, District::query()->where('ine_code', '2005')->count());
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
