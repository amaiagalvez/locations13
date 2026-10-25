<?php

namespace Locations13\Tests\Unit\Policies;

use Locations13\Models\Town;
use PHPUnit\Framework\TestCase;
use Locations13\Policies\TownPolicy;
use Basics13\Support\Testing\PermissionsActor;

class TownPolicyTest extends TestCase
{
    public function test_view_any_needs_the_view_permission(): void
    {
        $policy = new TownPolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['towns.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['towns.create'])));
    }

    public function test_create_needs_the_create_permission(): void
    {
        $policy = new TownPolicy;

        $this->assertTrue($policy->create(PermissionsActor::holding(['towns.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['towns.view'])));
    }

    public function test_update_edit_delete_restore_force_delete_archive_and_activate(): void
    {
        $policy = new TownPolicy;
        $town = (new Town)->forceFill(['id' => 1, 'name' => 'Urretxu']);

        $allowed = PermissionsActor::holding(['towns.manage']);
        $denied = PermissionsActor::holding([]);

        $this->assertTrue($policy->update($allowed, $town));
        $this->assertTrue($policy->delete($allowed, $town));
        $this->assertTrue($policy->restore($allowed, $town));
        $this->assertTrue($policy->forceDelete($allowed, $town));
        $this->assertTrue($policy->archive($allowed, $town));
        $this->assertTrue($policy->activate($allowed, $town));

        $this->assertFalse($policy->update($denied, $town));
        $this->assertFalse($policy->delete($denied, $town));
        $this->assertFalse($policy->restore($denied, $town));
        $this->assertFalse($policy->forceDelete($denied, $town));
        $this->assertFalse($policy->archive($denied, $town));
        $this->assertFalse($policy->activate($denied, $town));
    }

    public function test_view_grants_nothing_else(): void
    {
        $policy = new TownPolicy;
        $town = (new Town)->forceFill(['id' => 1, 'name' => 'Urretxu']);
        $viewer = PermissionsActor::holding(['towns.view']);

        $this->assertFalse($policy->update($viewer, $town));
        $this->assertFalse($policy->delete($viewer, $town));
    }
}
