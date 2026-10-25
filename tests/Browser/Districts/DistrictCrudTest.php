<?php

namespace Locations13\Tests\Browser\Districts;

use Laravel\Dusk\Browser;
use Locations13\Models\District;
use Locations13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The district drawer is filled in Alpine from the row payload, so create, edit and delete go through the
 * browser.
 */
class DistrictCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const SUBMIT_SELECTOR = 'dialog[open] [data-test="district-submit"]';

    private const STATUS_SELECTOR = '[data-test="district-status"]';

    public function test_admin_can_create_district(): void
    {
        $user = $this->admin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/locations/districts')
                ->click('[data-test="district-create-button"]')
                ->waitFor($this->field('name'))
                ->type($this->field('name'), 'Created district');

            $browser->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.created'));
        });

        $this->assertDatabaseHas(District::class, ['name' => 'Created district']);
    }

    public function test_admin_can_update_district(): void
    {
        $user = $this->admin();
        $district = District::factory()->create(['name' => 'Original district']);

        $this->browse(function (Browser $browser) use ($user, $district): void {
            $browser->loginAs($user)
                ->visit('/locations/districts')
                ->click("[data-test='district-edit-{$district->getKey()}']")
                ->waitFor($this->field('name'))
                ->assertInputValue($this->field('name'), 'Original district')
                ->clear($this->field('name'))
                ->type($this->field('name'), 'Updated district')
                ->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.updated'));
        });

        $this->assertSame('Updated district', $district->refresh()->name);
    }

    public function test_admin_can_delete_district(): void
    {
        $user = $this->admin();
        $district = District::factory()->create(['name' => 'To Be Deleted']);

        $this->browse(function (Browser $browser) use ($user, $district): void {
            $browser->loginAs($user)
                ->visit('/locations/districts')
                ->assertSeeIn("[data-test='district-name-{$district->getKey()}']", 'To Be Deleted')
                ->click("[data-test='district-delete-{$district->getKey()}']")
                ->waitFor('dialog[open] [data-test="district-confirm-submit"]')
                ->click('dialog[open] [data-test="district-confirm-submit"]')
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.moved_to_trash'));
        });

        $this->assertSoftDeleted($district);
    }

    private function field(string $field): string
    {
        return 'dialog[open] [data-test="district-'.$field.'"]';
    }
}
