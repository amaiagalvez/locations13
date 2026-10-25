<?php

namespace Locations13\Tests\Feature\States;

use App\Models\User;
use Locations13\Models\State;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class StateUniqueIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_name_cannot_be_repeated_among_the_states_not_in_the_trash(): void
    {
        $this->actingAs($this->user());
        State::factory()->create(['name' => 'Euskal Herria']);

        $this->from(route('states.index'))->post(route('states.store'), ['name' => 'Euskal Herria'])
            ->assertRedirect(route('states.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, State::query()->where('name', 'Euskal Herria')->count());
    }

    public function test_a_name_in_the_trash_may_be_reused(): void
    {
        $this->actingAs($this->user());
        State::factory()->create(['name' => 'Euskal Herria'])->delete();

        $this->from(route('states.index'))->post(route('states.store'), [
            'name' => 'Euskal Herria',
            'reuse_deleted_name' => '1',
        ])
            ->assertRedirect(route('states.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, State::query()->whereNull('deleted_at')->count());
    }

    public function test_updating_a_state_to_a_taken_name_is_rejected(): void
    {
        $this->actingAs($this->user());
        State::factory()->create(['name' => 'Euskal Herria']);
        $other = State::factory()->create(['name' => 'Estatu espainiarra']);

        $this->from(route('states.index'))->put(route('states.update', $other), ['name' => 'Euskal Herria'])
            ->assertRedirect(route('states.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame('Estatu espainiarra', $other->refresh()->name);
    }

    public function test_the_same_name_as_another_resource_passes(): void
    {
        $this->actingAs($this->user());
        Country::factory()->create(['name' => 'Zumaia']);

        $this->from(route('states.index'))->post(route('states.store'), ['name' => 'Zumaia'])
            ->assertRedirect(route('states.index'))
            ->assertSessionHasNoErrors();
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
