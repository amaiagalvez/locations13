<?php

namespace Locations13\Tests\Unit;

use Basics13\Tests\PackageIsolationTestCase;

/**
 * locations13 must work without entities13, projects13, the bridge or the host installed.
 */
class PackageIsolationTest extends PackageIsolationTestCase
{
    protected function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    protected function forbiddenInProductionCode(): array
    {
        return [
            'App\\',
            'Entities13\\',
            'EntityProjects13\\',
            'Projects13\\',
            'ENT_entities',
            'ENT_entity_types',
            'PRO_projects',
            "route('entities.",
            "route('projects.",
            "route('epics.",
        ];
    }

    protected function forbiddenInTests(): array
    {
        return [
            'Entities13\\',
            'EntityProjects13\\',
            'Projects13\\',
        ];
    }

    protected function isolationAdvice(): string
    {
        return 'locations13 is an independent domain package: it must work without entities13, '
            .'projects13 or the bridge installed, and nothing of this change relates locations to '
            .'entities or projects. Keep the geographic tree inside Locations13 only.';
    }
}
