<?php

namespace Locations13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Locations13\Models\Town;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Locations13\Transformers\TownListTransformer;

final class TownListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('locations/towns', fn () => null)->name('towns.index');
        Route::get('locations/towns/archived', fn () => null)->name('towns.archived.index');
        Route::get('locations/towns/trash', fn () => null)->name('towns.trash.index');
        Route::delete('locations/towns/{record}', fn () => null)->name('towns.destroy');
        Route::patch('locations/towns/{record}/archive', fn () => null)->name('towns.archive');
        Route::patch('locations/towns/archived/{record}', fn () => null)->name('towns.archived.activate');
        Route::patch('locations/towns/trash/{record}', fn () => null)->name('towns.trash.restore');
        Route::delete('locations/towns/trash/{record}', fn () => null)->name('towns.trash.destroy');
    }

    /**
     * A town without parents nor postal codes: the unit path force-fills the attributes and relations
     * it needs, without the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): Town
    {
        $record = (new Town)->forceFill([...$attributes, 'id' => $attributes['id'] ?? 1]);

        $record->setAttribute('neighbourhoods_count', $attributes['neighbourhoods_count'] ?? 0);

        $record->setRelation('postalCodes', new Collection);

        return $record;
    }

    public function test_confirmation_names_the_town_by_its_name(): void
    {
        $rows = (new TownListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Urretxu'])], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['town-delete-1'];

        $this->assertSame(
            __('basics13::messages.delete_record', ['name' => 'Urretxu']),
            $delete['confirmTitle'],
        );
    }

    public function test_a_town_children_point_at_offers_archive_instead_of_the_delete(): void
    {
        $rows = (new TownListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Urretxu', 'neighbourhoods_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'], null, 'test');

        $this->assertArrayHasKey('town-archive-1', $actions);
        $this->assertArrayNotHasKey('town-delete-1', $actions);
    }

    public function test_a_trashed_town_children_point_at_cannot_be_force_deleted(): void
    {
        $rows = (new TownListTransformer)->trash(new LengthAwarePaginator([$this->record(['name' => 'Urretxu', 'neighbourhoods_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayNotHasKey('town-force-delete-1', $actions);
    }

    public function test_the_edit_payload_carries_the_fields_the_form_edits(): void
    {
        $rows = (new TownListTransformer)->active(new LengthAwarePaginator(
            [$this->record(['name' => 'Urretxu', 'ine_code' => '01'])],
            1,
            15,
        ), '')['rows'];

        $this->assertSame([
            'id' => 1,
            'name' => 'Urretxu',
            'ine_code' => '01',
            'notes' => null,
            'district_id' => null,
            'district_label' => null,
            'district_active' => null,
            'postal_codes' => [],
        ], $rows[0]['editPayload']);
    }

    public function test_a_trashed_town_without_children_may_be_force_deleted(): void
    {
        $record = $this->record(['name' => 'Urretxu']);
        $record->setAttribute('active', false);

        $rows = (new TownListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayHasKey('town-force-delete-1', $actions);
    }
}
