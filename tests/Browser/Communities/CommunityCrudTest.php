<?php

namespace Locations13\Tests\Browser\Communities;

use Laravel\Dusk\Browser;
use Locations13\Models\Community;
use Locations13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The community drawer is filled in Alpine from the row payload, so create, edit and delete go through the
 * browser.
 */
class CommunityCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const SUBMIT_SELECTOR = 'dialog[open] [data-test="community-submit"]';

    private const STATUS_SELECTOR = '[data-test="community-status"]';

    public function test_admin_can_create_community(): void
    {
        $user = $this->admin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/locations/communities')
                ->click('[data-test="community-create-button"]')
                ->waitFor($this->field('name'))
                ->type($this->field('name'), 'Created community');

            $browser->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.created'));
        });

        $this->assertDatabaseHas(Community::class, ['name' => 'Created community']);
    }

    public function test_admin_can_update_community(): void
    {
        $user = $this->admin();
        $community = Community::factory()->create(['name' => 'Original community']);

        $this->browse(function (Browser $browser) use ($user, $community): void {
            $browser->loginAs($user)
                ->visit('/locations/communities')
                ->click("[data-test='community-edit-{$community->getKey()}']")
                ->waitFor($this->field('name'))
                ->assertInputValue($this->field('name'), 'Original community')
                ->clear($this->field('name'))
                ->type($this->field('name'), 'Updated community')
                ->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.updated'));
        });

        $this->assertSame('Updated community', $community->refresh()->name);
    }

    public function test_admin_can_delete_community(): void
    {
        $user = $this->admin();
        $community = Community::factory()->create(['name' => 'To Be Deleted']);

        $this->browse(function (Browser $browser) use ($user, $community): void {
            $browser->loginAs($user)
                ->visit('/locations/communities')
                ->assertSeeIn("[data-test='community-name-{$community->getKey()}']", 'To Be Deleted')
                ->click("[data-test='community-delete-{$community->getKey()}']")
                ->waitFor('dialog[open] [data-test="community-confirm-submit"]')
                ->click('dialog[open] [data-test="community-confirm-submit"]')
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.moved_to_trash'));
        });

        $this->assertSoftDeleted($community);
    }

    private function field(string $field): string
    {
        return 'dialog[open] [data-test="community-'.$field.'"]';
    }
}
