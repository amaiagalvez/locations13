<?php

namespace Locations13\Tests\Unit\Policies;

use PHPUnit\Framework\TestCase;
use Locations13\Models\Neighbourhood;
use Locations13\Policies\NeighbourhoodPolicy;
use Basics13\Support\Testing\PermissionsActor;

class NeighbourhoodPolicyTest extends TestCase
{
    public function test_view_any_needs_the_view_permission(): void
    {
        $policy = new NeighbourhoodPolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['neighbourhoods.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['neighbourhoods.create'])));
    }

    public function test_create_needs_the_create_permission(): void
    {
        $policy = new NeighbourhoodPolicy;

        $this->assertTrue($policy->create(PermissionsActor::holding(['neighbourhoods.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['neighbourhoods.view'])));
    }

    public function test_update_edit_delete_restore_force_delete_archive_and_activate(): void
    {
        $policy = new NeighbourhoodPolicy;
        $neighbourhood = (new Neighbourhood)->forceFill(['id' => 1, 'name' => 'Alde Zaharra']);

        $allowed = PermissionsActor::holding(['neighbourhoods.manage']);
        $denied = PermissionsActor::holding([]);

        $this->assertTrue($policy->update($allowed, $neighbourhood));
        $this->assertTrue($policy->delete($allowed, $neighbourhood));
        $this->assertTrue($policy->restore($allowed, $neighbourhood));
        $this->assertTrue($policy->forceDelete($allowed, $neighbourhood));
        $this->assertTrue($policy->archive($allowed, $neighbourhood));
        $this->assertTrue($policy->activate($allowed, $neighbourhood));

        $this->assertFalse($policy->update($denied, $neighbourhood));
        $this->assertFalse($policy->delete($denied, $neighbourhood));
        $this->assertFalse($policy->restore($denied, $neighbourhood));
        $this->assertFalse($policy->forceDelete($denied, $neighbourhood));
        $this->assertFalse($policy->archive($denied, $neighbourhood));
        $this->assertFalse($policy->activate($denied, $neighbourhood));
    }

    public function test_view_grants_nothing_else(): void
    {
        $policy = new NeighbourhoodPolicy;
        $neighbourhood = (new Neighbourhood)->forceFill(['id' => 1, 'name' => 'Alde Zaharra']);
        $viewer = PermissionsActor::holding(['neighbourhoods.view']);

        $this->assertFalse($policy->update($viewer, $neighbourhood));
        $this->assertFalse($policy->delete($viewer, $neighbourhood));
    }
}
