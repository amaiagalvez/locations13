<?php

namespace Locations13\Tests\Feature\Permissions;

use Basics13\Models\Role;
use Locations13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Locations13\Database\Seeders\LocationsAccessSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class LocationPermissionSeedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_gets_full_access_to_the_six_location_modules(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                'countries.manage',
                'states.manage',
                'communities.manage',
                'districts.manage',
                'towns.manage',
                'neighbourhoods.manage',
            ],
            Role::query()->where('slug', 'admin')->firstOrFail()->permissions()
                ->whereIn('module', ['countries', 'states', 'communities', 'districts', 'towns', 'neighbourhoods'])
                ->pluck('slug')->all(),
        );
    }

    public function test_running_it_again_duplicates_nothing(): void
    {
        $counts = [DB::table('BAS_permissions')->count(), DB::table('BAS_permission_role')->count()];

        $this->seed(LocationsAccessSeeder::class);

        $this->assertSame($counts, [DB::table('BAS_permissions')->count(), DB::table('BAS_permission_role')->count()]);
    }
}
