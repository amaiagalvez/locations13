<?php

namespace Locations13\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The permissions the six location policies check and the locations group of the sidebar. `admin`
 * gets full access to the six modules. Runs after the basics13 access seeder, which creates
 * `admin`; running it again duplicates nothing and takes no permission away from a role.
 */
class LocationsAccessSeeder extends Seeder
{
    /** @var list<string> */
    private const MODULES = ['countries', 'states', 'communities', 'districts', 'towns', 'neighbourhoods'];

    /**
     * In tree order: the state holds the communities, the communities the herrialdeak, those the
     * comarcas, those the towns and those the neighbourhoods. Each link asks for the permission
     * its list's policy checks.
     *
     * @var list<array{key: string, label: string, sort: int, parent?: string, icon?: string, route?: string, active?: string, except?: string, permissions?: list<string>}>
     */
    private const MENUS = [
        ['key' => 'locations13', 'label' => 'Locations package', 'sort' => 400],
        ['key' => 'states', 'parent' => 'locations13', 'label' => 'States', 'sort' => 10, 'icon' => 'globe-europe-africa', 'route' => 'states.index', 'active' => 'states.*', 'permissions' => ['states.view']],
        ['key' => 'communities', 'parent' => 'locations13', 'label' => 'Communities', 'sort' => 20, 'icon' => 'flag', 'route' => 'communities.index', 'active' => 'communities.*', 'permissions' => ['communities.view']],
        ['key' => 'countries', 'parent' => 'locations13', 'label' => 'Countries', 'sort' => 30, 'icon' => 'map', 'route' => 'countries.index', 'active' => 'countries.*', 'permissions' => ['countries.view']],
        ['key' => 'districts', 'parent' => 'locations13', 'label' => 'Districts', 'sort' => 40, 'icon' => 'map-pin', 'route' => 'districts.index', 'active' => 'districts.*', 'permissions' => ['districts.view']],
        ['key' => 'towns', 'parent' => 'locations13', 'label' => 'Towns', 'sort' => 50, 'icon' => 'building-office-2', 'route' => 'towns.index', 'active' => 'towns.*', 'permissions' => ['towns.view']],
        ['key' => 'neighbourhoods', 'parent' => 'locations13', 'label' => 'Neighbourhoods', 'sort' => 60, 'icon' => 'home', 'route' => 'neighbourhoods.index', 'active' => 'neighbourhoods.*', 'permissions' => ['neighbourhoods.view']],
    ];

    public function run(): void
    {
        seedPermissions(self::MODULES, array_map(static fn (string $module): string => $module.'.manage', self::MODULES));
        seedMenus('locations13', self::MENUS);
    }
}
