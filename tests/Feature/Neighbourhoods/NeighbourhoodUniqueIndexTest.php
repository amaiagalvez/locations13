<?php

namespace Locations13\Tests\Feature\Neighbourhoods;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Locations13\Models\Neighbourhood;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class NeighbourhoodUniqueIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_name_cannot_be_repeated_inside_the_same_town(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $town->id]);

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Iparragirre',
            'town_id' => (string) $town->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Neighbourhood::query()->where('town_id', $town->id)->count());
    }

    public function test_the_same_name_is_accepted_in_two_different_towns(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $other = Town::factory()->create();
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $town->id]);

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Iparragirre',
            'town_id' => (string) $other->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Neighbourhood::query()->where('town_id', $other->id)->count());
    }

    public function test_a_name_in_the_trash_may_be_reused_inside_its_town(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $town->id])->delete();

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Iparragirre',
            'town_id' => (string) $town->id,
            'reuse_deleted_name' => '1',
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Neighbourhood::query()->whereNull('deleted_at')->count());
    }

    public function test_moving_a_neighbourhood_to_another_town_respects_its_names(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $other = Town::factory()->create();
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $town->id]);
        $neighbourhood = Neighbourhood::factory()->create(['name' => 'Elizkale', 'town_id' => $other->id]);

        $this->from(route('neighbourhoods.index'))->put(route('neighbourhoods.update', $neighbourhood), [
            'name' => 'Iparragirre',
            'town_id' => (string) $other->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Iparragirre', $neighbourhood->refresh()->name);
    }

    public function test_an_ine_code_cannot_repeat_among_the_neighbourhoods_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $other = Town::factory()->create();
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $town->id, 'ine_code' => '200580001']);

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Elizkale',
            'town_id' => (string) $other->id,
            'ine_code' => '200580001',
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHasErrors('ine_code');

        $this->assertSame(1, Neighbourhood::query()->where('ine_code', '200580001')->count());
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
