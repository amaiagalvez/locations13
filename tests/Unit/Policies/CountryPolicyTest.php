<?php

namespace Locations13\Tests\Unit\Policies;

use Locations13\Models\Country;
use PHPUnit\Framework\TestCase;
use Locations13\Policies\CountryPolicy;
use Basics13\Support\Testing\PermissionsActor;

class CountryPolicyTest extends TestCase
{
    public function test_view_any_needs_the_view_permission(): void
    {
        $policy = new CountryPolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['countries.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['countries.create'])));
    }

    public function test_create_needs_the_create_permission(): void
    {
        $policy = new CountryPolicy;

        $this->assertTrue($policy->create(PermissionsActor::holding(['countries.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['countries.view'])));
    }

    public function test_update_edit_delete_restore_force_delete_archive_and_activate(): void
    {
        $policy = new CountryPolicy;
        $country = (new Country)->forceFill(['id' => 1, 'name' => 'Euskal Herria']);

        $allowed = PermissionsActor::holding(['countries.manage']);
        $denied = PermissionsActor::holding([]);

        $this->assertTrue($policy->update($allowed, $country));
        $this->assertTrue($policy->delete($allowed, $country));
        $this->assertTrue($policy->restore($allowed, $country));
        $this->assertTrue($policy->forceDelete($allowed, $country));
        $this->assertTrue($policy->archive($allowed, $country));
        $this->assertTrue($policy->activate($allowed, $country));

        $this->assertFalse($policy->update($denied, $country));
        $this->assertFalse($policy->delete($denied, $country));
        $this->assertFalse($policy->restore($denied, $country));
        $this->assertFalse($policy->forceDelete($denied, $country));
        $this->assertFalse($policy->archive($denied, $country));
        $this->assertFalse($policy->activate($denied, $country));
    }

    public function test_view_grants_nothing_else(): void
    {
        $policy = new CountryPolicy;
        $country = (new Country)->forceFill(['id' => 1, 'name' => 'Euskal Herria']);
        $viewer = PermissionsActor::holding(['countries.view']);

        $this->assertFalse($policy->update($viewer, $country));
        $this->assertFalse($policy->delete($viewer, $country));
    }
}
