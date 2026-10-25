<?php

namespace Locations13\Tests\Feature\Neighbourhoods;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Locations13\Models\Neighbourhood;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class NeighbourhoodTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('neighbourhoods.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_neighbourhoods_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Neighbourhood::factory()->create(['name' => 'Iparragirre']);
        Neighbourhood::factory()->trashed()->create(['name' => 'Elizkale']);

        $this->get(route('neighbourhoods.index'))
            ->assertOk()
            ->assertSee('Iparragirre')
            ->assertDontSee('Elizkale');

        $this->get(route('neighbourhoods.trash.index'))
            ->assertOk()
            ->assertSee('Elizkale')
            ->assertDontSee('Iparragirre');
    }

    public function test_a_trashed_neighbourhood_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $neighbourhood = Neighbourhood::factory()->trashed()->create();

        $this->patch(route('neighbourhoods.trash.restore', $neighbourhood->id))
            ->assertRedirect(route('neighbourhoods.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($neighbourhood);
    }

    public function test_a_trashed_neighbourhood_cannot_be_restored_while_its_town_uses_its_name(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $neighbourhood = Neighbourhood::factory()->trashed()->create([
            'name' => 'Iparragirre',
            'town_id' => $town->id,
        ]);
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $town->id]);

        $this->patch(route('neighbourhoods.trash.restore', $neighbourhood->id))
            ->assertRedirect(route('neighbourhoods.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($neighbourhood);
    }

    public function test_a_trashed_neighbourhood_from_another_town_may_be_restored_with_the_same_name(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $other = Town::factory()->create();
        $neighbourhood = Neighbourhood::factory()->trashed()->create([
            'name' => 'Iparragirre',
            'town_id' => $town->id,
        ]);
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $other->id]);

        $this->patch(route('neighbourhoods.trash.restore', $neighbourhood->id))
            ->assertRedirect(route('neighbourhoods.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($neighbourhood);
    }

    public function test_a_trashed_neighbourhood_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $neighbourhood = Neighbourhood::factory()->trashed()->create();

        $this->delete(route('neighbourhoods.trash.destroy', $neighbourhood->id))
            ->assertRedirect(route('neighbourhoods.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('LOC_neighbourhoods', ['id' => $neighbourhood->id]);
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
