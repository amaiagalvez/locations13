<?php

namespace Locations13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Locations13\Transformers\DistrictListTransformer;

final class DistrictListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('locations/districts', fn () => null)->name('districts.index');
        Route::get('locations/districts/archived', fn () => null)->name('districts.archived.index');
        Route::get('locations/districts/trash', fn () => null)->name('districts.trash.index');
        Route::delete('locations/districts/{record}', fn () => null)->name('districts.destroy');
        Route::patch('locations/districts/{record}/archive', fn () => null)->name('districts.archive');
        Route::patch('locations/districts/archived/{record}', fn () => null)->name('districts.archived.activate');
        Route::patch('locations/districts/trash/{record}', fn () => null)->name('districts.trash.restore');
        Route::delete('locations/districts/trash/{record}', fn () => null)->name('districts.trash.destroy');
    }

    /**
     * A district without parents: the unit path force-fills the attributes it needs, without the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): District
    {
        $record = (new District)->forceFill([...$attributes, 'id' => $attributes['id'] ?? 1]);

        $record->setAttribute('towns_count', $attributes['towns_count'] ?? 0);

        return $record;
    }

    public function test_confirmation_names_the_district_by_its_name(): void
    {
        $rows = (new DistrictListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Goierri'])], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['district-delete-1'];

        $this->assertSame(
            __('basics13::messages.delete_record', ['name' => 'Goierri']),
            $delete['confirmTitle'],
        );
    }

    public function test_a_district_children_point_at_offers_archive_instead_of_the_delete(): void
    {
        $rows = (new DistrictListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Goierri', 'towns_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'], null, 'test');

        $this->assertArrayHasKey('district-archive-1', $actions);
        $this->assertArrayNotHasKey('district-delete-1', $actions);
    }

    public function test_a_trashed_district_children_point_at_cannot_be_force_deleted(): void
    {
        $rows = (new DistrictListTransformer)->trash(new LengthAwarePaginator([$this->record(['name' => 'Goierri', 'towns_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayNotHasKey('district-force-delete-1', $actions);
    }

    public function test_the_edit_payload_carries_the_fields_the_form_edits(): void
    {
        $rows = (new DistrictListTransformer)->active(new LengthAwarePaginator(
            [$this->record(['name' => 'Goierri', 'ine_code' => '01'])],
            1,
            15,
        ), '')['rows'];

        $this->assertSame([
            'id' => 1,
            'name' => 'Goierri',
            'ine_code' => '01',
            'notes' => null,
            'country_id' => null,
            'country_label' => null,
            'country_active' => null,
        ], $rows[0]['editPayload']);
    }

    public function test_a_trashed_district_without_children_may_be_force_deleted(): void
    {
        $record = $this->record(['name' => 'Goierri']);
        $record->setAttribute('active', false);

        $rows = (new DistrictListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayHasKey('district-force-delete-1', $actions);
    }
}
