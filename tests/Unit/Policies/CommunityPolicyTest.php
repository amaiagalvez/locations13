<?php

namespace Locations13\Tests\Unit\Policies;

use PHPUnit\Framework\TestCase;
use Locations13\Models\Community;
use Locations13\Policies\CommunityPolicy;
use Basics13\Support\Testing\PermissionsActor;

class CommunityPolicyTest extends TestCase
{
    public function test_view_any_needs_the_view_permission(): void
    {
        $policy = new CommunityPolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['communities.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['communities.create'])));
    }

    public function test_create_needs_the_create_permission(): void
    {
        $policy = new CommunityPolicy;

        $this->assertTrue($policy->create(PermissionsActor::holding(['communities.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['communities.view'])));
    }

    public function test_update_edit_delete_restore_force_delete_archive_and_activate(): void
    {
        $policy = new CommunityPolicy;
        $community = (new Community)->forceFill(['id' => 1, 'name' => 'Euskadi']);

        $allowed = PermissionsActor::holding(['communities.manage']);
        $denied = PermissionsActor::holding([]);

        $this->assertTrue($policy->update($allowed, $community));
        $this->assertTrue($policy->delete($allowed, $community));
        $this->assertTrue($policy->restore($allowed, $community));
        $this->assertTrue($policy->forceDelete($allowed, $community));
        $this->assertTrue($policy->archive($allowed, $community));
        $this->assertTrue($policy->activate($allowed, $community));

        $this->assertFalse($policy->update($denied, $community));
        $this->assertFalse($policy->delete($denied, $community));
        $this->assertFalse($policy->restore($denied, $community));
        $this->assertFalse($policy->forceDelete($denied, $community));
        $this->assertFalse($policy->archive($denied, $community));
        $this->assertFalse($policy->activate($denied, $community));
    }

    public function test_view_grants_nothing_else(): void
    {
        $policy = new CommunityPolicy;
        $community = (new Community)->forceFill(['id' => 1, 'name' => 'Euskadi']);
        $viewer = PermissionsActor::holding(['communities.view']);

        $this->assertFalse($policy->update($viewer, $community));
        $this->assertFalse($policy->delete($viewer, $community));
    }
}
