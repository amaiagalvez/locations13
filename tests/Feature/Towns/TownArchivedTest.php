<?php

namespace Locations13\Tests\Feature\Towns;

use App\Models\User;
use Locations13\Models\Town;
use Locations13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class TownArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_towns_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        Town::factory()->archived()->create(['name' => 'Andosilla']);
        Town::factory()->create(['name' => 'Urretxu']);

        $this->get(route('towns.index'))
            ->assertOk()
            ->assertSee('Urretxu')
            ->assertDontSee('Andosilla');

        $this->get(route('towns.archived.index'))
            ->assertOk()
            ->assertSee('Andosilla')
            ->assertDontSee('Urretxu');
    }

    public function test_archived_towns_are_searchable_by_name(): void
    {
        $this->actingAs($this->user());
        Town::factory()->archived()->create(['name' => 'Andosilla']);
        Town::factory()->archived()->create(['name' => 'Donostia']);

        $this->get(route('towns.archived.index', ['search' => 'Ando']))
            ->assertOk()
            ->assertSee('Andosilla')
            ->assertDontSee('Donostia');
    }

    public function test_a_town_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $town = Town::factory()->create();

        $this->patch(route('towns.archive', $town))
            ->assertRedirect(route('towns.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($town->refresh()->active);

        $this->patch(route('towns.archived.activate', $town))
            ->assertRedirect(route('towns.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($town->refresh()->active);
    }

    public function test_the_unique_name_index_keeps_archived_and_new_towns_apart(): void
    {
        $this->actingAs($this->user());
        Town::factory()->archived()->create(['name' => 'Andosilla']);

        $this->from(route('towns.index'))->post(route('towns.store'), ['name' => 'Andosilla'])
            ->assertRedirect(route('towns.index'))
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
