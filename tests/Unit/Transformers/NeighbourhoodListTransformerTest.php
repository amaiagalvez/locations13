<?php

namespace Locations13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Locations13\Models\Town;
use Illuminate\Support\Facades\Route;
use Locations13\Models\Neighbourhood;
use Illuminate\Pagination\LengthAwarePaginator;
use Locations13\Transformers\NeighbourhoodListTransformer;

final class NeighbourhoodListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('locations/neighbourhoods', fn () => null)->name('neighbourhoods.index');
        Route::get('locations/neighbourhoods/archived', fn () => null)->name('neighbourhoods.archived.index');
        Route::get('locations/neighbourhoods/trash', fn () => null)->name('neighbourhoods.trash.index');
        Route::delete('locations/neighbourhoods/{record}', fn () => null)->name('neighbourhoods.destroy');
        Route::patch('locations/neighbourhoods/{record}/archive', fn () => null)->name('neighbourhoods.archive');
        Route::patch('locations/neighbourhoods/archived/{record}', fn () => null)->name('neighbourhoods.archived.activate');
        Route::patch('locations/neighbourhoods/trash/{record}', fn () => null)->name('neighbourhoods.trash.restore');
        Route::delete('locations/neighbourhoods/trash/{record}', fn () => null)->name('neighbourhoods.trash.destroy');
    }

    /**
     * A neighbourhood always hangs from a town, so the unit path gives it one without the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): Neighbourhood
    {
        $record = (new Neighbourhood)->forceFill([...$attributes, 'id' => $attributes['id'] ?? 1, 'town_id' => 1]);

        $record->setRelation('town', (new Town)->forceFill(['id' => 1, 'name' => 'Urretxu', 'active' => true]));

        return $record;
    }

    public function test_confirmation_names_the_neighbourhood_by_its_name(): void
    {
        $rows = (new NeighbourhoodListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Alde Zaharra'])], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['neighbourhood-delete-1'];

        $this->assertSame(
            __('basics13::messages.delete_record', ['name' => 'Alde Zaharra']),
            $delete['confirmTitle'],
        );
    }

    public function test_a_neighbourhood_is_never_blocked_from_the_delete(): void
    {
        $rows = (new NeighbourhoodListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Alde Zaharra'])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'], null, 'test');

        $this->assertArrayHasKey('neighbourhood-delete-1', $actions);
    }

    public function test_the_edit_payload_carries_the_fields_the_form_edits(): void
    {
        $rows = (new NeighbourhoodListTransformer)->active(new LengthAwarePaginator(
            [$this->record(['name' => 'Alde Zaharra', 'ine_code' => '01'])],
            1,
            15,
        ), '')['rows'];

        $this->assertSame([
            'id' => 1,
            'name' => 'Alde Zaharra',
            'ine_code' => '01',
            'notes' => null,
            'town_id' => 1,
            'town_label' => 'Urretxu',
            'town_active' => true,
        ], $rows[0]['editPayload']);
    }

    public function test_a_trashed_neighbourhood_without_children_may_be_force_deleted(): void
    {
        $record = $this->record(['name' => 'Alde Zaharra']);
        $record->setAttribute('active', false);

        $rows = (new NeighbourhoodListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayHasKey('neighbourhood-force-delete-1', $actions);
    }
}
