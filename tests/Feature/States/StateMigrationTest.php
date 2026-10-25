<?php

namespace Locations13\Tests\Feature\States;

use Locations13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class StateMigrationTest extends TestCase
{
    public function test_state_migration_can_be_rolled_back(): void
    {
        $connectionName = 'locations13_state_migration_test';
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

        try {
            Schema::create('users', static function (Blueprint $table): void {
                $table->id();
            });

            $state->up();

            $this->assertTrue(Schema::hasTable('LOC_states'));
            $this->assertTrue(Schema::hasColumns('LOC_states', [
                'name', 'ine_code', 'notes', 'active',
                'created_by', 'updated_by', 'deleted_by',
                'created_at', 'updated_at', 'deleted_at',
            ]));

            $state->down();
            $this->assertFalse(Schema::hasTable('LOC_states'));
        } finally {
            DB::purge($connectionName);
            config([
                'database.default' => $originalDefault,
                "database.connections.{$connectionName}" => null,
            ]);
        }
    }
}
