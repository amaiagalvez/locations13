<?php

namespace Locations13\Tests\Feature\Towns;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Locations13\Models\PostalCode;
use Locations13\Models\Neighbourhood;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class TownTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('towns.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_towns_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Town::factory()->create(['name' => 'Urretxu']);
        Town::factory()->trashed()->create(['name' => 'Donostia']);

        $this->get(route('towns.index'))
            ->assertOk()
            ->assertSee('Urretxu')
            ->assertDontSee('Donostia');

        $this->get(route('towns.trash.index'))
            ->assertOk()
            ->assertSee('Donostia')
            ->assertDontSee('Urretxu');
    }

    public function test_a_trashed_town_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->trashed()->create();

        $this->patch(route('towns.trash.restore', $town->id))
            ->assertRedirect(route('towns.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($town);
    }

    public function test_a_trashed_town_cannot_be_restored_while_a_live_town_uses_its_name(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->trashed()->create(['name' => 'Urretxu']);
        Town::factory()->create(['name' => 'Urretxu']);

        $this->patch(route('towns.trash.restore', $town->id))
            ->assertRedirect(route('towns.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($town);
    }

    public function test_a_town_without_neighbourhoods_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->trashed()->create();

        $this->delete(route('towns.trash.destroy', $town->id))
            ->assertRedirect(route('towns.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('LOC_towns', ['id' => $town->id]);
    }

    public function test_moving_a_town_to_the_trash_takes_its_postal_codes_with_it(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $town->postalCodes()->createMany([['code' => '20700'], ['code' => '20701']]);

        $this->delete(route('towns.destroy', $town))
            ->assertRedirect(route('towns.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));

        $this->assertSoftDeleted($town);
        $this->assertSame([], $town->refresh()->postalCodes()->pluck('code')->all());

        $codes = $town->postalCodes()->onlyTrashed()->orderBy('code')->get();
        $this->assertSame(['20700', '20701'], $codes->pluck('code')->all());
    }

    public function test_restoring_a_town_brings_its_postal_codes_back(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        $town->postalCodes()->createMany([['code' => '20700'], ['code' => '20701']]);

        $town->delete();
        $this->patch(route('towns.trash.restore', $town->id))
            ->assertRedirect(route('towns.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($town);
        $this->assertSame(['20700', '20701'], $town->refresh()->postalCodes()->orderBy('code')->pluck('code')->all());
    }

    public function test_a_postal_code_removed_in_an_edit_does_not_come_back_with_the_restore(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create(['name' => 'Urretxu']);
        $town->postalCodes()->createMany([['code' => '20700'], ['code' => '20701']]);

        $this->put(route('towns.update', $town), ['name' => 'Urretxu', 'postal_codes' => ['20700']])->assertSessionHasNoErrors();
        $town->delete();
        $this->patch(route('towns.trash.restore', $town->id))->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertSame(['20700'], $town->refresh()->postalCodes()->pluck('code')->all());
        $this->assertSame(0, PostalCode::onlyTrashed()->where('town_id', $town->id)->count());
    }

    public function test_postal_codes_never_block_the_permanent_delete(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->trashed()->create();
        PostalCode::factory()->for($town)->create();

        $this->delete(route('towns.trash.destroy', $town->id))
            ->assertRedirect(route('towns.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseCount('LOC_postal_codes', 0);
    }

    public function test_a_trashed_postal_code_does_not_block_the_permanent_delete_either(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->trashed()->create();
        PostalCode::factory()->for($town)->create();
        $town->postalCodes()->sole()->delete();

        $this->delete(route('towns.trash.destroy', $town->id))
            ->assertRedirect(route('towns.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseCount('LOC_postal_codes', 0);
    }

    public function test_a_town_with_a_neighbourhood_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();
        Neighbourhood::factory()->create(['town_id' => $town->id]);

        $this->delete(route('towns.destroy', $town))
            ->assertRedirect(route('towns.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($town);
    }

    public function test_a_town_with_a_trashed_neighbourhood_cannot_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->trashed()->create();
        Neighbourhood::factory()->trashed()->create(['town_id' => $town->id]);

        $this->delete(route('towns.trash.destroy', $town->id))
            ->assertRedirect(route('towns.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));

        $this->assertDatabaseHas('LOC_towns', ['id' => $town->id]);
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
