<?php

namespace Locations13\Tests\Feature\Import;

use Locations13\Models\Town;
use Locations13\Models\State;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Locations13\Models\Community;
use Illuminate\Testing\PendingCommand;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class ImportEuskadiCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        // tempnam() is deprecated in PHP 8.5; use a unique path under sys_get_temp_dir()
        $this->file = sys_get_temp_dir().'/locations13-import-'.uniqid('', true);
        touch($this->file);
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }

        parent::tearDown();
    }

    public function test_it_writes_the_whole_tree_and_assigns_the_parents(): void
    {
        $this->writeCatalog();

        $this->command(['--file' => $this->file])
            ->expectsOutputToContain('Import: 5 records written, 0 updated, 0 conflicts.')
            ->assertSuccessful();

        $state = State::query()->where('ine_code', 'EH')->sole();
        $community = Community::query()->where('ine_code', '16')->sole();
        $country = Country::query()->where('ine_code', '20')->sole();
        $district = District::query()->where('ine_code', '2005')->sole();
        $town = Town::query()->where('ine_code', '20058')->sole();

        $this->assertSame($state->id, $community->state_id);
        $this->assertSame($community->id, $country->community_id);
        $this->assertSame($country->id, $district->country_id);
        $this->assertSame($district->id, $town->district_id);
    }

    public function test_running_it_again_duplicates_nothing(): void
    {
        $this->writeCatalog();

        $this->command(['--file' => $this->file])->assertSuccessful();
        $this->command(['--file' => $this->file])
            ->expectsOutputToContain('0 records written, 5 updated, 0 conflicts.')
            ->assertSuccessful();

        $this->assertDatabaseCount('LOC_states', 1);
        $this->assertDatabaseCount('LOC_communities', 1);
        $this->assertDatabaseCount('LOC_countries', 1);
        $this->assertDatabaseCount('LOC_districts', 1);
        $this->assertDatabaseCount('LOC_towns', 1);
    }

    public function test_a_record_made_by_hand_is_reported_as_a_conflict_and_left_alone(): void
    {
        $manual = Town::factory()->create(['name' => 'Urretxu', 'ine_code' => null]);
        $this->writeCatalog();

        $this->command(['--file' => $this->file])
            ->expectsOutputToContain('4 records written, 0 updated, 1 conflicts.')
            ->assertSuccessful();

        $this->assertSame(1, Town::query()->where('name', 'Urretxu')->count());
        $this->assertNull($manual->refresh()->ine_code);
    }

    public function test_an_existing_code_updates_its_name_and_parent_without_duplicating(): void
    {
        $district = District::factory()->create(['name' => 'Zahar eskualdea', 'ine_code' => '2005']);
        $town = Town::factory()->create([
            'name' => 'Zahar herria',
            'ine_code' => '20058',
            'district_id' => null,
        ]);
        $this->writeCatalog();

        $this->command(['--file' => $this->file])->assertSuccessful();

        $this->assertSame('Urretxu', $town->refresh()->name);
        $this->assertSame($district->id, $town->district_id);
        $this->assertDatabaseCount('LOC_towns', 1);
    }

    public function test_an_unresolved_parent_is_not_written_over_the_one_the_row_has(): void
    {
        $district = District::factory()->create(['name' => 'Goierri', 'ine_code' => null]);
        $town = Town::factory()->create([
            'name' => 'Zahara',
            'ine_code' => '20058',
            'district_id' => $district->id,
        ]);

        $this->writeCatalog();

        $this->command(['--file' => $this->file])->assertSuccessful();

        $this->assertSame($district->id, $town->refresh()->district_id);
    }

    public function test_a_name_taken_by_another_code_is_warned_and_skipped(): void
    {
        $taken = State::factory()->create(['name' => 'Euskal Herria', 'ine_code' => 'XX']);
        file_put_contents($this->file, "level,ine_code,name,parent_code\nstate,EH,Euskal Herria,\n");

        $this->command(['--file' => $this->file])
            ->expectsOutputToContain('taken by another record')
            ->assertSuccessful();

        $this->assertDatabaseCount('LOC_states', 1);
        $this->assertSame('XX', $taken->refresh()->ine_code);
    }

    public function test_an_incomplete_row_is_skipped(): void
    {
        file_put_contents($this->file, "level,ine_code,name,parent_code\nstate,,Euskal Herria,\n");

        $this->command(['--file' => $this->file])
            ->expectsOutputToContain('skipped')
            ->assertSuccessful();

        $this->assertDatabaseCount('LOC_states', 0);
    }

    public function test_a_missing_file_fails(): void
    {
        $this->command(['--file' => '/tmp/no-such-locations13.csv'])
            ->assertFailed();
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function command(array $arguments = []): PendingCommand
    {
        $command = $this->artisan('locations:import-euskadi', $arguments);
        $this->assertInstanceOf(PendingCommand::class, $command);

        return $command;
    }

    private function writeCatalog(): void
    {
        file_put_contents($this->file, implode("\n", [
            'level,ine_code,name,parent_code',
            'state,EH,Euskal Herria,',
            'community,16,EAE,EH',
            'country,20,Gipuzkoa,16',
            'district,2005,Goierri,20',
            'town,20058,Urretxu,2005',
            '',
        ]));
    }
}
