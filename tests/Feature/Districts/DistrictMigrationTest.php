<?php

namespace Locations13\Tests\Feature\Districts;

use Locations13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DistrictMigrationTest extends TestCase
{
    public function test_district_migration_can_be_rolled_back(): void
    {
        $connectionName = 'locations13_district_migration_test';
        $originalDefault = config('database.default');
        $sqliteConnection = config('database.connections.sqlite');

        if (! is_array($sqliteConnection)) {
            throw new \RuntimeException('The SQLite test connection is not configured.');
        }

        config([
            "database.connections.{$connectionName}" => array_merge($sqliteConnection, [
                'database' => ':memory:',
            ]),
            'database.default' => $connectionName,
        ]);

        DB::purge($connectionName);

        $migrations = dirname(__DIR__, 3).'/database/migrations';

        $state = require $migrations.'/2026_10_24_100000_create_loc_states_table.php';
        $community = require $migrations.'/2026_10_24_100010_create_loc_communities_table.php';
        $country = require $migrations.'/2026_10_24_100020_create_loc_countries_table.php';
        $district = require $migrations.'/2026_10_24_100030_create_loc_districts_table.php';

        try {
            Schema::create('users', static function (Blueprint $table): void {
                $table->id();
            });

            $state->up();
            $community->up();
            $country->up();
            $district->up();

            $this->assertTrue(Schema::hasTable('LOC_districts'));
            $this->assertTrue(Schema::hasColumns('LOC_districts', ['name', 'ine_code', 'country_id', 'notes', 'active', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at']));

            $district->down();
            $country->down();
            $community->down();
            $state->down();
            $this->assertFalse(Schema::hasTable('LOC_districts'));
        } finally {
            DB::purge($connectionName);
            config([
                'database.default' => $originalDefault,
                "database.connections.{$connectionName}" => null,
            ]);
        }
    }
}
