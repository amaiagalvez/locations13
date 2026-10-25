<?php

namespace Locations13\Tests\Feature\Neighbourhoods;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Locations13\Models\Neighbourhood;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class NeighbourhoodArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_neighbourhoods_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Neighbourhood::factory()->archived()->create(['name' => 'Elizkale']);
        Neighbourhood::factory()->create(['name' => 'Iparragirre']);

        $this->get(route('neighbourhoods.index'))
            ->assertOk()
            ->assertSee('Iparragirre')
            ->assertDontSee('Elizkale');

        $this->get(route('neighbourhoods.archived.index'))
            ->assertOk()
            ->assertSee('Elizkale')
            ->assertDontSee('Iparragirre');
    }

    public function test_archived_neighbourhoods_are_searchable_by_name(): void
    {
        $this->actingAs($this->user());
        Neighbourhood::factory()->archived()->create(['name' => 'Elizkale']);
        Neighbourhood::factory()->archived()->create(['name' => 'Iparragirre']);

        $this->get(route('neighbourhoods.archived.index', ['search' => 'Eliz']))
            ->assertOk()
            ->assertSee('Elizkale')
            ->assertDontSee('Iparragirre');
    }

    public function test_a_neighbourhood_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $neighbourhood = Neighbourhood::factory()->create();

        $this->patch(route('neighbourhoods.archive', $neighbourhood))
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($neighbourhood->refresh()->active);

        $this->patch(route('neighbourhoods.archived.activate', $neighbourhood))
            ->assertRedirect(route('neighbourhoods.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($neighbourhood->refresh()->active);
    }

    public function test_the_unique_name_index_keeps_archived_and_new_neighbourhoods_apart_inside_the_town(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        Neighbourhood::factory()->archived()->create(['name' => 'Elizkale', 'town_id' => $town->id]);

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Elizkale',
            'town_id' => (string) $town->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
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
