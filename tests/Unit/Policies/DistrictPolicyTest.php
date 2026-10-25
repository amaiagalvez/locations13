<?php

namespace Locations13\Tests\Unit\Policies;

use PHPUnit\Framework\TestCase;
use Locations13\Models\District;
use Locations13\Policies\DistrictPolicy;
use Basics13\Support\Testing\PermissionsActor;

class DistrictPolicyTest extends TestCase
{
    public function test_view_any_needs_the_view_permission(): void
    {
        $policy = new DistrictPolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['districts.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['districts.create'])));
    }

    public function test_create_needs_the_create_permission(): void
    {
        $policy = new DistrictPolicy;

        $this->assertTrue($policy->create(PermissionsActor::holding(['districts.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['districts.view'])));
    }

    public function test_update_edit_delete_restore_force_delete_archive_and_activate(): void
    {
        $policy = new DistrictPolicy;
        $district = (new District)->forceFill(['id' => 1, 'name' => 'Goierri']);

        $allowed = PermissionsActor::holding(['districts.manage']);
        $denied = PermissionsActor::holding([]);

        $this->assertTrue($policy->update($allowed, $district));
        $this->assertTrue($policy->delete($allowed, $district));
        $this->assertTrue($policy->restore($allowed, $district));
        $this->assertTrue($policy->forceDelete($allowed, $district));
        $this->assertTrue($policy->archive($allowed, $district));
        $this->assertTrue($policy->activate($allowed, $district));

        $this->assertFalse($policy->update($denied, $district));
        $this->assertFalse($policy->delete($denied, $district));
        $this->assertFalse($policy->restore($denied, $district));
        $this->assertFalse($policy->forceDelete($denied, $district));
        $this->assertFalse($policy->archive($denied, $district));
        $this->assertFalse($policy->activate($denied, $district));
    }

    public function test_view_grants_nothing_else(): void
    {
        $policy = new DistrictPolicy;
        $district = (new District)->forceFill(['id' => 1, 'name' => 'Goierri']);
        $viewer = PermissionsActor::holding(['districts.view']);

        $this->assertFalse($policy->update($viewer, $district));
        $this->assertFalse($policy->delete($viewer, $district));
    }
}
