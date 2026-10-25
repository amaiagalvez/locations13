<?php

namespace Locations13\Tests\Feature\Countries;

use App\Models\User;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CountryUniqueIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_name_cannot_be_repeated_among_the_countries_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        $country = Country::factory()->create(['name' => 'Euskal Herria']);

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Euskal Herria'])
            ->assertRedirect(route('countries.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Country::query()->where('name', 'Euskal Herria')->count());
        $country->refresh();
    }

    public function test_an_archived_country_still_keeps_its_name(): void
    {
        $this->actingAs($this->user());
        Country::factory()->archived()->create(['name' => 'Euskal Herria']);

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Euskal Herria'])
            ->assertRedirect(route('countries.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Country::query()->withTrashed()->count());
    }

    public function test_a_name_in_the_trash_may_be_reused(): void
    {
        $this->actingAs($this->user());
        $trashed = Country::factory()->create(['name' => 'Euskal Herria']);
        $trashed->delete();

        // The conflict modal offers the creation with the trashed name; its button posts the
        // hidden reuse flag the store validates.
        $this->from(route('countries.index'))->post(route('countries.store'), [
            'name' => 'Euskal Herria',
            'reuse_deleted_name' => '1',
        ])
            ->assertRedirect(route('countries.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Country::query()->whereNull('deleted_at')->count());
    }

    public function test_updating_a_country_to_a_taken_name_is_rejected(): void
    {
        $this->actingAs($this->user());
        Country::factory()->create(['name' => 'Euskal Herria']);
        $other = Country::factory()->create(['name' => 'Iparraldea']);

        $this->from(route('countries.index'))->put(route('countries.update', $other), ['name' => 'Euskal Herria'])
            ->assertRedirect(route('countries.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame('Iparraldea', $other->refresh()->name);
    }

    public function test_an_ine_code_cannot_repeat_among_the_countries_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        Country::factory()->create(['name' => 'Euskal Herria', 'ine_code' => 'EH']);

        $this->from(route('countries.index'))->post(route('countries.store'), ['name' => 'Iparraldea', 'ine_code' => 'EH'])
            ->assertRedirect(route('countries.index'))
            ->assertSessionHasErrors('ine_code');

        $this->assertSame(1, Country::query()->where('ine_code', 'EH')->count());
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
