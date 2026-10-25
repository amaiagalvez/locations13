<?php

namespace Locations13\Tests\Feature\Navigation;

use Basics13\Models\User;
use Locations13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Basics13\Support\NavigationMenu;
use Locations13\Database\Seeders\LocationsAccessSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class LocationMenuSeedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_running_it_again_duplicates_nothing(): void
    {
        $counts = [DB::table('BAS_menus')->count(), DB::table('BAS_menu_permission')->count()];

        $this->seed(LocationsAccessSeeder::class);

        $this->assertSame($counts, [DB::table('BAS_menus')->count(), DB::table('BAS_menu_permission')->count()]);
    }

    public function test_the_group_lists_the_six_resources_in_tree_order(): void
    {
        $locations = collect(NavigationMenu::for(User::factory()->withPermissions([
            'states.view', 'communities.view', 'countries.view',
            'districts.view', 'towns.view', 'neighbourhoods.view',
        ])->create()))->firstWhere('item.key', 'locations13');

        $this->assertSame(
            ['states', 'communities', 'countries', 'districts', 'towns', 'neighbourhoods'],
            array_column(array_column($locations['children'] ?? [], 'item'), 'key'),
        );
    }

    public function test_each_link_asks_for_its_list_permission(): void
    {
        $locations = collect(NavigationMenu::for(User::factory()->withPermissions(['towns.view'])->create()))
            ->firstWhere('item.key', 'locations13');

        $this->assertSame(['towns'], array_column(array_column($locations['children'] ?? [], 'item'), 'key'));
        $this->assertNull(collect(NavigationMenu::for(User::factory()->create()))->firstWhere('item.key', 'locations13'));
    }

    public function test_the_old_locations_group_is_gone(): void
    {
        DB::table('BAS_menus')->insert(['key' => 'locations', 'package' => 'locations13', 'label' => 'Locations', 'sort' => 300, 'created_at' => now(), 'updated_at' => now()]);

        $this->seed(LocationsAccessSeeder::class);

        $this->assertFalse(DB::table('BAS_menus')->where('key', 'locations')->exists());
    }
}
