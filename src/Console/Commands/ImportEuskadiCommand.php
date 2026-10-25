<?php

namespace Locations13\Console\Commands;

use Locations13\Models\Town;
use Locations13\Models\State;
use Illuminate\Console\Command;
use Locations13\Models\Country;
use Locations13\Models\District;
use Locations13\Models\Community;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

/**
 * Loads the official catalogues of Euskal Herria from a CSV prepared on disk, so no network
 * reaches the import and the command can be run again whenever a host wants to.
 *
 * The CSV has a header and one row per record, with these columns:
 *
 *     level,ine_code,name,parent_code
 *     state,EH,Euskal Herria,
 *     community,16,EAE,EH
 *     country,20,Gipuzkoa,16
 *     district,2005,Goierri,20
 *     town,20058,Urretxu,2005
 *
 * `level` selects the table the row fills, `ine_code` is the official code the row is upserted by
 * and `parent_code` is the official code of the parent the row hangs from; when it resolves to an
 * existing parent it is assigned, and otherwise the row is written without one. A record a user
 * made by hand, without `ine_code`, is never touched: when its name collides with a catalog row
 * the row is reported as a conflict instead.
 *
 * The CSV file MUST be UTF-8 encoded. Non-UTF-8 files (e.g., Latin-1 from legacy INE/Eustat exports)
 * will produce mojibake or skipped rows. Convert with `iconv -f ISO-8859-1 -t UTF-8 input.csv > output.csv`
 * before importing.
 */
class ImportEuskadiCommand extends Command
{
    protected $signature = 'locations:import-euskadi
                            {--file= : CSV file to read, defaults to config(locations13.import.file)}';

    protected $description = 'Load the official INE/Eustat/INSEE localities of Euskal Herria from a prepared CSV';

    /**
     * Each level names the model and table it fills, and the parent level the `parent_code` column
     * links to. A parent code resolves inside the parent's own table, by `ine_code`.
     *
     * @var array<string, array{model: class-string, table: string, parent: ?class-string, parentColumn: ?string}>
     */
    private const LEVELS = [
        'state' => ['model' => State::class, 'table' => 'LOC_states', 'parent' => null, 'parentColumn' => null],
        'community' => ['model' => Community::class, 'table' => 'LOC_communities', 'parent' => State::class, 'parentColumn' => 'state_id'],
        'country' => ['model' => Country::class, 'table' => 'LOC_countries', 'parent' => Community::class, 'parentColumn' => 'community_id'],
        'district' => ['model' => District::class, 'table' => 'LOC_districts', 'parent' => Country::class, 'parentColumn' => 'country_id'],
        'town' => ['model' => Town::class, 'table' => 'LOC_towns', 'parent' => District::class, 'parentColumn' => 'district_id'],
    ];

    public function handle(): int
    {
        $file = $this->option('file');
        $file = is_string($file) && $file !== '' ? $file : config('locations13.import.file');

        if (! is_string($file) || ! is_file($file)) {
            $this->error(__('There is no catalog CSV to import. Prepare one and pass it with --file=: :file.', ['file' => $file]));

            return self::FAILURE;
        }

        $handle = fopen($file, 'rb');

        if ($handle === false) {
            $this->error(__('The catalog CSV cannot be opened: :file.', ['file' => $file]));

            return self::FAILURE;
        }

        $header = null;
        $written = 0;
        $updated = 0;
        $conflicts = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(static fn (mixed $column): string => mb_strtolower(trim((string) $column)), $row);

                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), ''));

            $level = mb_strtolower(trim($data['level'] ?? ''));
            $ineCode = trim($data['ine_code'] ?? '');
            $name = trim($data['name'] ?? '');
            $parentCode = trim($data['parent_code'] ?? '');

            if (! isset(self::LEVELS[$level]) || $ineCode === '' || $name === '') {
                $this->warn(__('A catalog row is not complete and is skipped: :row.', ['row' => implode(',', $row)]));

                continue;
            }

            $catalog = self::LEVELS[$level];

            try {
                $handled = $this->upsert($catalog, $name, $ineCode, $parentCode);
            } catch (QueryException) {
                $this->warn(__('The official name/code pair of the row is taken by another record: '
                    .':level, ine_code :code, :name.', ['level' => $level, 'code' => $ineCode, 'name' => $name]));

                continue;
            }

            match ($handled) {
                'conflict' => $conflicts++,
                'updated' => $updated++,
                default => $written++,
            };

            if ($handled === 'conflict') {
                $this->warn(__('The official code :code already holds a name a record without a code took, '
                    .':name: the row is left alone.', ['code' => $ineCode, 'name' => $name]));
            }
        }

        fclose($handle);

        $this->info(__('Import: :written records written, :updated updated, :conflicts conflicts.', [
            'written' => $written,
            'updated' => $updated,
            'conflicts' => $conflicts,
        ]));

        return self::SUCCESS;
    }

    /**
     * The columns the import fills: the name from the row and, when the parent code resolves, the
     * parent the row hangs from.
     *
     * @param  array{model: class-string, table: string, parent: ?class-string, parentColumn: ?string}  $catalog
     * @return 'written'|'updated'|'conflict'
     */
    private function upsert(array $catalog, string $name, string $ineCode, string $parentCode): string
    {
        $modelClass = $catalog['model'];
        $existing = $modelClass::query()->where('ine_code', $ineCode)->first();

        if ($existing === null) {
            $manual = $catalog['model']::query()
                ->whereNull('ine_code')
                ->where('name', $name)
                ->first();

            if ($manual !== null) {
                return 'conflict';
            }

            DB::table($catalog['table'])->insert([
                'name' => $name,
                'ine_code' => $ineCode,
                ...$this->parentColumns($catalog, $parentCode),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 'written';
        }

        $existing->update(['name' => $name, ...$this->parentColumns($catalog, $parentCode)]);

        return 'updated';
    }

    /**
     * The parent column the row fills, when the code resolves to a record of the parent table. An
     * unknown or absent code writes nothing, so the import never wipes a parent an admin assigned
     * and a row keeps the one it had.
     *
     * @param  array{model: class-string, table: string, parent: ?class-string, parentColumn: ?string}  $catalog
     * @return array<string, mixed>
     */
    private function parentColumns(array $catalog, string $parentCode): array
    {
        $column = $catalog['parentColumn'];

        if ($column === null || $catalog['parent'] === null || $parentCode === '') {
            return [];
        }

        $parentId = $catalog['parent']::query()->whereNull('deleted_at')->where('ine_code', $parentCode)->value('id');

        return $parentId !== null ? [$column => (int) $parentId] : [];
    }
}
