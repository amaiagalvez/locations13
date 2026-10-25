<?php

namespace Locations13\Tests\Feature\Towns;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Models\State;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class TownUniqueIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_name_cannot_be_repeated_among_the_towns_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        Town::factory()->create(['name' => 'Urretxu']);

        $this->from(route('towns.index'))->post(route('towns.store'), ['name' => 'Urretxu'])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Town::query()->where('name', 'Urretxu')->count());
    }

    public function test_a_name_in_the_trash_may_be_reused(): void
    {
        $this->actingAs($this->user());
        Town::factory()->create(['name' => 'Urretxu'])->delete();

        $this->from(route('towns.index'))->post(route('towns.store'), [
            'name' => 'Urretxu',
            'reuse_deleted_name' => '1',
        ])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Town::query()->whereNull('deleted_at')->count());
    }

    public function test_updating_a_town_to_a_taken_name_is_rejected(): void
    {
        $this->actingAs($this->user());
        Town::factory()->create(['name' => 'Urretxu']);
        $other = Town::factory()->create(['name' => 'Donostia']);

        $this->from(route('towns.index'))->put(route('towns.update', $other), ['name' => 'Urretxu'])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame('Donostia', $other->refresh()->name);
    }

    public function test_the_same_name_as_another_resource_passes(): void
    {
        $this->actingAs($this->user());
        State::factory()->create(['name' => 'Zumaia']);

        $this->from(route('towns.index'))->post(route('towns.store'), ['name' => 'Zumaia'])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasNoErrors();
    }

    public function test_an_ine_code_cannot_repeat_among_the_towns_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        Town::factory()->create(['name' => 'Urretxu', 'ine_code' => '20058']);

        $this->from(route('towns.index'))->post(route('towns.store'), ['name' => 'Zumarraga', 'ine_code' => '20058'])
            ->assertRedirect(route('towns.index'))
            ->assertSessionHasErrors('ine_code');

        $this->assertSame(1, Town::query()->where('ine_code', '20058')->count());
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
