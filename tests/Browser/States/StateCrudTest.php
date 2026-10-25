<?php

namespace Locations13\Tests\Browser\States;

use Laravel\Dusk\Browser;
use Locations13\Models\State;
use Locations13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The state drawer is filled in Alpine from the row payload, so create, edit and delete go through the
 * browser.
 */
class StateCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const SUBMIT_SELECTOR = 'dialog[open] [data-test="state-submit"]';

    private const STATUS_SELECTOR = '[data-test="state-status"]';

    public function test_admin_can_create_state(): void
    {
        $user = $this->admin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/locations/states')
                ->click('[data-test="state-create-button"]')
                ->waitFor($this->field('name'))
                ->type($this->field('name'), 'Created state');

            $browser->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.created'));
        });

        $this->assertDatabaseHas(State::class, ['name' => 'Created state']);
    }

    public function test_admin_can_update_state(): void
    {
        $user = $this->admin();
        $state = State::factory()->create(['name' => 'Original state']);

        $this->browse(function (Browser $browser) use ($user, $state): void {
            $browser->loginAs($user)
                ->visit('/locations/states')
                ->click("[data-test='state-edit-{$state->getKey()}']")
                ->waitFor($this->field('name'))
                ->assertInputValue($this->field('name'), 'Original state')
                ->clear($this->field('name'))
                ->type($this->field('name'), 'Updated state')
                ->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.updated'));
        });

        $this->assertSame('Updated state', $state->refresh()->name);
    }

    public function test_admin_can_delete_state(): void
    {
        $user = $this->admin();
        $state = State::factory()->create(['name' => 'To Be Deleted']);

        $this->browse(function (Browser $browser) use ($user, $state): void {
            $browser->loginAs($user)
                ->visit('/locations/states')
                ->assertSeeIn("[data-test='state-name-{$state->getKey()}']", 'To Be Deleted')
                ->click("[data-test='state-delete-{$state->getKey()}']")
                ->waitFor('dialog[open] [data-test="state-confirm-submit"]')
                ->click('dialog[open] [data-test="state-confirm-submit"]')
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.moved_to_trash'));
        });

        $this->assertSoftDeleted($state);
    }

    private function field(string $field): string
    {
        return 'dialog[open] [data-test="state-'.$field.'"]';
    }
}
