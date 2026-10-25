<?php

namespace Locations13\Tests\Browser\Countries;

use Laravel\Dusk\Browser;
use Locations13\Models\Country;
use Locations13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The country drawer is filled in Alpine from the row payload, so create, edit and delete go through the
 * browser.
 */
class CountryCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const SUBMIT_SELECTOR = 'dialog[open] [data-test="country-submit"]';

    private const STATUS_SELECTOR = '[data-test="country-status"]';

    public function test_admin_can_create_country(): void
    {
        $user = $this->admin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/locations/countries')
                ->click('[data-test="country-create-button"]')
                ->waitFor($this->field('name'))
                ->type($this->field('name'), 'Created country');

            $browser->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.created'));
        });

        $this->assertDatabaseHas(Country::class, ['name' => 'Created country']);
    }

    public function test_admin_can_update_country(): void
    {
        $user = $this->admin();
        $country = Country::factory()->create(['name' => 'Original country']);

        $this->browse(function (Browser $browser) use ($user, $country): void {
            $browser->loginAs($user)
                ->visit('/locations/countries')
                ->click("[data-test='country-edit-{$country->getKey()}']")
                ->waitFor($this->field('name'))
                ->assertInputValue($this->field('name'), 'Original country')
                ->clear($this->field('name'))
                ->type($this->field('name'), 'Updated country')
                ->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.updated'));
        });

        $this->assertSame('Updated country', $country->refresh()->name);
    }

    public function test_admin_can_delete_country(): void
    {
        $user = $this->admin();
        $country = Country::factory()->create(['name' => 'To Be Deleted']);

        $this->browse(function (Browser $browser) use ($user, $country): void {
            $browser->loginAs($user)
                ->visit('/locations/countries')
                ->assertSeeIn("[data-test='country-name-{$country->getKey()}']", 'To Be Deleted')
                ->click("[data-test='country-delete-{$country->getKey()}']")
                ->waitFor('dialog[open] [data-test="country-confirm-submit"]')
                ->click('dialog[open] [data-test="country-confirm-submit"]')
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.moved_to_trash'));
        });

        $this->assertSoftDeleted($country);
    }

    private function field(string $field): string
    {
        return 'dialog[open] [data-test="country-'.$field.'"]';
    }
}
