<?php

namespace Locations13\Tests\Feature\Towns;

use Locations13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TownMigrationTest extends TestCase
{
    public function test_town_and_postal_code_migrations_can_be_rolled_back(): void
    {
        $connectionName = 'locations13_town_migration_test';
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
        $town = require $migrations.'/2026_10_24_100040_create_loc_towns_table.php';
        $postalCodes = require $migrations.'/2026_10_24_100065_create_loc_postal_codes_table.php';

        try {
            Schema::create('users', static function (Blueprint $table): void {
                $table->id();
            });

            $state->up();
            $community->up();
            $country->up();
            $district->up();
            $town->up();
            $postalCodes->up();

            $this->assertTrue(Schema::hasTable('LOC_towns'));
            $this->assertTrue(Schema::hasTable('LOC_postal_codes'));
            $this->assertTrue(Schema::hasColumns('LOC_towns', ['name', 'ine_code', 'district_id', 'notes', 'active', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at']));
            $this->assertTrue(Schema::hasColumns('LOC_postal_codes', [
                'town_id', 'code', 'notes', 'active',
                'created_by', 'updated_by', 'deleted_by',
                'created_at', 'updated_at', 'deleted_at',
            ]));

            $postalCodes->down();
            $town->down();
            $district->down();
            $country->down();
            $community->down();
            $state->down();

            $this->assertFalse(Schema::hasTable('LOC_postal_codes'));
            $this->assertFalse(Schema::hasTable('LOC_towns'));
        } finally {
            DB::purge($connectionName);
            config([
                'database.default' => $originalDefault,
                "database.connections.{$connectionName}" => null,
            ]);
        }
    }
}
