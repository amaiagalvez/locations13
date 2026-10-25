<?php

namespace Locations13\Tests\Unit\Policies;

use Locations13\Models\State;
use PHPUnit\Framework\TestCase;
use Locations13\Policies\StatePolicy;
use Basics13\Support\Testing\PermissionsActor;

class StatePolicyTest extends TestCase
{
    public function test_view_any_needs_the_view_permission(): void
    {
        $policy = new StatePolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['states.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['states.create'])));
    }

    public function test_create_needs_the_create_permission(): void
    {
        $policy = new StatePolicy;

        $this->assertTrue($policy->create(PermissionsActor::holding(['states.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['states.view'])));
    }

    public function test_update_edit_delete_restore_force_delete_archive_and_activate(): void
    {
        $policy = new StatePolicy;
        $state = (new State)->forceFill(['id' => 1, 'name' => 'Espainia']);

        $allowed = PermissionsActor::holding(['states.manage']);
        $denied = PermissionsActor::holding([]);

        $this->assertTrue($policy->update($allowed, $state));
        $this->assertTrue($policy->delete($allowed, $state));
        $this->assertTrue($policy->restore($allowed, $state));
        $this->assertTrue($policy->forceDelete($allowed, $state));
        $this->assertTrue($policy->archive($allowed, $state));
        $this->assertTrue($policy->activate($allowed, $state));

        $this->assertFalse($policy->update($denied, $state));
        $this->assertFalse($policy->delete($denied, $state));
        $this->assertFalse($policy->restore($denied, $state));
        $this->assertFalse($policy->forceDelete($denied, $state));
        $this->assertFalse($policy->archive($denied, $state));
        $this->assertFalse($policy->activate($denied, $state));
    }

    public function test_view_grants_nothing_else(): void
    {
        $policy = new StatePolicy;
        $state = (new State)->forceFill(['id' => 1, 'name' => 'Espainia']);
        $viewer = PermissionsActor::holding(['states.view']);

        $this->assertFalse($policy->update($viewer, $state));
        $this->assertFalse($policy->delete($viewer, $state));
    }
}
