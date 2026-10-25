<?php

namespace Locations13\Tests\Browser\Towns;

use Laravel\Dusk\Browser;
use Locations13\Models\Town;
use Locations13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The town drawer is filled in Alpine from the row payload, so create, edit and delete go through the
 * browser.
 */
class TownCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const SUBMIT_SELECTOR = 'dialog[open] [data-test="town-submit"]';

    private const STATUS_SELECTOR = '[data-test="town-status"]';

    public function test_admin_can_create_town(): void
    {
        $user = $this->admin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/locations/towns')
                ->click('[data-test="town-create-button"]')
                ->waitFor($this->field('name'))
                ->type($this->field('name'), 'Created town');

            $browser->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.created'));
        });

        $this->assertDatabaseHas(Town::class, ['name' => 'Created town']);
    }

    public function test_admin_can_update_town(): void
    {
        $user = $this->admin();
        $town = Town::factory()->create(['name' => 'Original town']);

        $this->browse(function (Browser $browser) use ($user, $town): void {
            $browser->loginAs($user)
                ->visit('/locations/towns')
                ->click("[data-test='town-edit-{$town->getKey()}']")
                ->waitFor($this->field('name'))
                ->assertInputValue($this->field('name'), 'Original town')
                ->clear($this->field('name'))
                ->type($this->field('name'), 'Updated town')
                ->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.updated'));
        });

        $this->assertSame('Updated town', $town->refresh()->name);
    }

    public function test_admin_can_delete_town(): void
    {
        $user = $this->admin();
        $town = Town::factory()->create(['name' => 'To Be Deleted']);

        $this->browse(function (Browser $browser) use ($user, $town): void {
            $browser->loginAs($user)
                ->visit('/locations/towns')
                ->assertSeeIn("[data-test='town-name-{$town->getKey()}']", 'To Be Deleted')
                ->click("[data-test='town-delete-{$town->getKey()}']")
                ->waitFor('dialog[open] [data-test="town-confirm-submit"]')
                ->click('dialog[open] [data-test="town-confirm-submit"]')
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.moved_to_trash'));
        });

        $this->assertSoftDeleted($town);
    }

    private function field(string $field): string
    {
        return 'dialog[open] [data-test="town-'.$field.'"]';
    }
}
