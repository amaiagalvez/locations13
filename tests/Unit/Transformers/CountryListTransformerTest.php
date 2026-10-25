<?php

namespace Locations13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Locations13\Models\Country;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Locations13\Transformers\CountryListTransformer;

final class CountryListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('locations/countries', fn () => null)->name('countries.index');
        Route::get('locations/countries/archived', fn () => null)->name('countries.archived.index');
        Route::get('locations/countries/trash', fn () => null)->name('countries.trash.index');
        Route::delete('locations/countries/{record}', fn () => null)->name('countries.destroy');
        Route::patch('locations/countries/{record}/archive', fn () => null)->name('countries.archive');
        Route::patch('locations/countries/archived/{record}', fn () => null)->name('countries.archived.activate');
        Route::patch('locations/countries/trash/{record}', fn () => null)->name('countries.trash.restore');
        Route::delete('locations/countries/trash/{record}', fn () => null)->name('countries.trash.destroy');
    }

    /**
     * A country without parents: the definition resolves a community through the database, which is
     * not available here, so the unit path force-fills the attributes it needs.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): Country
    {
        $record = (new Country)->forceFill([...$attributes, 'id' => $attributes['id'] ?? 1]);

        foreach (['districts_count', 'towns_count'] as $count) {
            $record->setAttribute($count, $attributes[$count] ?? 0);
        }

        return $record;
    }

    public function test_confirmation_names_the_herrialde_by_its_name(): void
    {
        $rows = (new CountryListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Araba'])], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['country-delete-1'];

        $this->assertSame(
            __('basics13::messages.delete_record', ['name' => 'Araba']),
            $delete['confirmTitle'],
        );
    }

    public function test_a_country_children_point_at_offers_archive_instead_of_the_delete(): void
    {
        $rows = (new CountryListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Araba', 'districts_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'], null, 'test');

        $this->assertArrayHasKey('country-archive-1', $actions);
        $this->assertArrayNotHasKey('country-delete-1', $actions);
    }

    public function test_the_edit_payload_carries_the_fields_the_form_edits(): void
    {
        $rows = (new CountryListTransformer)->active(new LengthAwarePaginator(
            [$this->record(['name' => 'Araba', 'ine_code' => '01'])],
            1,
            15,
        ), '')['rows'];

        $this->assertSame([
            'id' => 1,
            'name' => 'Araba',
            'ine_code' => '01',
            'notes' => null,
            'community_id' => null,
            'community_label' => null,
            'community_active' => null,
        ], $rows[0]['editPayload']);
    }

    public function test_a_trashed_town_may_be_restored_to_the_archived_list(): void
    {
        $record = $this->record(['name' => 'Araba']);
        $record->setAttribute('active', false);

        $rows = (new CountryListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayHasKey('country-force-delete-1', $actions);
    }
}
