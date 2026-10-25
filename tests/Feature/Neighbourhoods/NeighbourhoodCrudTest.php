<?php

namespace Locations13\Tests\Feature\Neighbourhoods;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Locations13\Models\Neighbourhood;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class NeighbourhoodCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('neighbourhoods.index'))->assertRedirect(route('login'));
        $this->post(route('neighbourhoods.store'), ['name' => 'Guest neighbourhood'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('LOC_neighbourhoods', 0);
    }

    public function test_users_without_the_neighbourhoods_permission_receive_a_403(): void
    {
        $user = User::create([
            'name' => 'No neighbourhood access',
            'email' => 'no-access-neighbourhood@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $this->get(route('neighbourhoods.index'))->assertForbidden();
        $this->post(route('neighbourhoods.store'), ['name' => 'Forbidden neighbourhood'])->assertForbidden();
        $this->assertDatabaseCount('LOC_neighbourhoods', 0);
    }

    public function test_an_admin_can_create_update_and_delete_a_neighbourhood(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create(['name' => 'Urretxu']);

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Iparragirre',
            'ine_code' => '200580001',
            'town_id' => (string) $town->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $neighbourhood = Neighbourhood::query()->sole();
        $this->assertSame('Iparragirre', $neighbourhood->name);
        $this->assertSame($town->id, $neighbourhood->town_id);

        $this->get(route('neighbourhoods.index'))
            ->assertOk()
            ->assertSee('neighbourhood-create')
            ->assertSee('neighbourhood-edit-'.$neighbourhood->id)
            ->assertSee('Iparragirre')
            ->assertSee('Urretxu');

        $this->from(route('neighbourhoods.index'))->put(route('neighbourhoods.update', $neighbourhood), [
            'name' => 'Iparragirre berria',
            'ine_code' => '',
            'town_id' => (string) $town->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('LOC_neighbourhoods', [
            'id' => $neighbourhood->id,
            'name' => 'Iparragirre berria',
            'ine_code' => null,
        ]);

        $this->delete(route('neighbourhoods.destroy', $neighbourhood))
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($neighbourhood);
    }

    public function test_a_neighbourhood_without_a_town_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), ['name' => 'Iparragirre'])
            ->assertSessionHasErrors('town_id');

        $this->assertDatabaseCount('LOC_neighbourhoods', 0);
    }

    public function test_a_town_that_does_not_exist_is_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Iparragirre',
            'town_id' => 999999,
        ])->assertSessionHasErrors('town_id');
    }

    public function test_two_neighbourhoods_of_the_same_town_are_accepted(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Iparragirre',
            'town_id' => (string) $town->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHasNoErrors();

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'Elizkale',
            'town_id' => (string) $town->id,
        ])
            ->assertRedirect(route('neighbourhoods.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Neighbourhood::query()->where('town_id', $town->id)->count());
    }

    public function test_the_list_search_filters_by_name(): void
    {
        $this->actingAs($this->user());
        Neighbourhood::factory()->create(['name' => 'Iparragirre']);
        Neighbourhood::factory()->create(['name' => 'Elizkale']);

        $this->get(route('neighbourhoods.index', ['search' => 'Iparra']))
            ->assertOk()
            ->assertSee('Iparragirre')
            ->assertDontSee('Elizkale');

        $this->get(route('neighbourhoods.index', ['search' => 'Eliz']))
            ->assertOk()
            ->assertDontSee('Iparragirre')
            ->assertSee('Elizkale');
    }

    public function test_the_name_is_trimmed_and_a_short_one_is_rejected(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => '  Iparragirre  ',
            'town_id' => (string) $town->id,
        ])->assertRedirect(route('neighbourhoods.index'));
        $this->assertDatabaseHas('LOC_neighbourhoods', ['name' => 'Iparragirre']);

        $this->from(route('neighbourhoods.index'))->post(route('neighbourhoods.store'), [
            'name' => 'A',
            'town_id' => (string) $town->id,
        ])->assertSessionHasErrors('name');
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
