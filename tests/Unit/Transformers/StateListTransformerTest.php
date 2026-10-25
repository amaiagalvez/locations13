<?php

namespace Locations13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Locations13\Models\State;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Locations13\Transformers\StateListTransformer;

final class StateListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('locations/states', fn () => null)->name('states.index');
        Route::get('locations/states/archived', fn () => null)->name('states.archived.index');
        Route::get('locations/states/trash', fn () => null)->name('states.trash.index');
        Route::delete('locations/states/{record}', fn () => null)->name('states.destroy');
        Route::patch('locations/states/{record}/archive', fn () => null)->name('states.archive');
        Route::patch('locations/states/archived/{record}', fn () => null)->name('states.archived.activate');
        Route::patch('locations/states/trash/{record}', fn () => null)->name('states.trash.restore');
        Route::delete('locations/states/trash/{record}', fn () => null)->name('states.trash.destroy');
    }

    /**
     * A state without parents: the unit path force-fills the attributes it needs, without the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): State
    {
        $record = (new State)->forceFill([...$attributes, 'id' => $attributes['id'] ?? 1]);

        $record->setAttribute('communities_count', $attributes['communities_count'] ?? 0);

        return $record;
    }

    public function test_confirmation_names_the_state_by_its_name(): void
    {
        $rows = (new StateListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Espainia'])], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['state-delete-1'];

        $this->assertSame(
            __('basics13::messages.delete_record', ['name' => 'Espainia']),
            $delete['confirmTitle'],
        );
    }

    public function test_a_state_children_point_at_offers_archive_instead_of_the_delete(): void
    {
        $rows = (new StateListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Espainia', 'communities_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'], null, 'test');

        $this->assertArrayHasKey('state-archive-1', $actions);
        $this->assertArrayNotHasKey('state-delete-1', $actions);
    }

    public function test_a_trashed_state_children_point_at_cannot_be_force_deleted(): void
    {
        $rows = (new StateListTransformer)->trash(new LengthAwarePaginator([$this->record(['name' => 'Espainia', 'communities_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayNotHasKey('state-force-delete-1', $actions);
    }

    public function test_the_edit_payload_carries_the_fields_the_form_edits(): void
    {
        $rows = (new StateListTransformer)->active(new LengthAwarePaginator(
            [$this->record(['name' => 'Espainia', 'ine_code' => '01'])],
            1,
            15,
        ), '')['rows'];

        $this->assertSame([
            'id' => 1,
            'name' => 'Espainia',
            'ine_code' => '01',
            'notes' => null,
        ], $rows[0]['editPayload']);
    }

    public function test_a_trashed_state_without_children_may_be_force_deleted(): void
    {
        $record = $this->record(['name' => 'Espainia']);
        $record->setAttribute('active', false);

        $rows = (new StateListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayHasKey('state-force-delete-1', $actions);
    }
}
