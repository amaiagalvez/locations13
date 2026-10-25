<?php

namespace Locations13\Tests\Feature\Countries;

use Locations13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CountryMigrationTest extends TestCase
{
    public function test_country_migration_can_be_rolled_back(): void
    {
        $connectionName = 'locations13_country_migration_test';
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

        try {
            Schema::create('users', static function (Blueprint $table): void {
                $table->id();
            });

            $state->up();
            $community->up();
            $country->up();

            $this->assertTrue(Schema::hasTable('LOC_countries'));
            $this->assertTrue(Schema::hasColumns('LOC_countries', ['name', 'ine_code', 'community_id', 'notes', 'active', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at']));

            $country->down();
            $community->down();
            $state->down();
            $this->assertFalse(Schema::hasTable('LOC_countries'));
        } finally {
            DB::purge($connectionName);
            config([
                'database.default' => $originalDefault,
                "database.connections.{$connectionName}" => null,
            ]);
        }
    }
}
