<?php

namespace Locations13\Tests\Browser\Neighbourhoods;

use Laravel\Dusk\Browser;
use Locations13\Models\Town;
use Locations13\Tests\DuskTestCase;
use Locations13\Models\Neighbourhood;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The neighbourhood drawer is filled in Alpine from the row payload, so create, edit and delete go through the
 * browser.
 */
class NeighbourhoodCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const SUBMIT_SELECTOR = 'dialog[open] [data-test="neighbourhood-submit"]';

    private const STATUS_SELECTOR = '[data-test="neighbourhood-status"]';

    public function test_admin_can_create_neighbourhood(): void
    {
        $user = $this->admin();
        $town = Town::factory()->create(['name' => 'Parent town']);

        $this->browse(function (Browser $browser) use ($user, $town): void {
            $browser->loginAs($user)
                ->visit('/locations/neighbourhoods')
                ->click('[data-test="neighbourhood-create-button"]')
                ->waitFor($this->field('name'))
                ->type($this->field('name'), 'Created neighbourhood');

            $browser->click($this->field('town'))
                ->waitFor($this->field('town').' option[value="'.$town->getKey().'"]')
                ->select($this->field('town'), (string) $town->getKey());

            $browser->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.created'));
        });

        $this->assertDatabaseHas(Neighbourhood::class, ['name' => 'Created neighbourhood']);
    }

    public function test_admin_can_update_neighbourhood(): void
    {
        $user = $this->admin();
        $neighbourhood = Neighbourhood::factory()->create(['name' => 'Original neighbourhood']);

        $this->browse(function (Browser $browser) use ($user, $neighbourhood): void {
            $browser->loginAs($user)
                ->visit('/locations/neighbourhoods')
                ->click("[data-test='neighbourhood-edit-{$neighbourhood->getKey()}']")
                ->waitFor($this->field('name'))
                ->assertInputValue($this->field('name'), 'Original neighbourhood')
                ->clear($this->field('name'))
                ->type($this->field('name'), 'Updated neighbourhood')
                ->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.updated'));
        });

        $this->assertSame('Updated neighbourhood', $neighbourhood->refresh()->name);
    }

    public function test_admin_can_delete_neighbourhood(): void
    {
        $user = $this->admin();
        $neighbourhood = Neighbourhood::factory()->create(['name' => 'To Be Deleted']);

        $this->browse(function (Browser $browser) use ($user, $neighbourhood): void {
            $browser->loginAs($user)
                ->visit('/locations/neighbourhoods')
                ->assertSeeIn("[data-test='neighbourhood-name-{$neighbourhood->getKey()}']", 'To Be Deleted')
                ->click("[data-test='neighbourhood-delete-{$neighbourhood->getKey()}']")
                ->waitFor('dialog[open] [data-test="neighbourhood-confirm-submit"]')
                ->click('dialog[open] [data-test="neighbourhood-confirm-submit"]')
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.moved_to_trash'));
        });

        $this->assertSoftDeleted($neighbourhood);
    }

    private function field(string $field): string
    {
        return 'dialog[open] [data-test="neighbourhood-'.$field.'"]';
    }
}
