<?php

namespace Locations13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Locations13\Models\Community;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Locations13\Transformers\CommunityListTransformer;

final class CommunityListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('locations/communities', fn () => null)->name('communities.index');
        Route::get('locations/communities/archived', fn () => null)->name('communities.archived.index');
        Route::get('locations/communities/trash', fn () => null)->name('communities.trash.index');
        Route::delete('locations/communities/{record}', fn () => null)->name('communities.destroy');
        Route::patch('locations/communities/{record}/archive', fn () => null)->name('communities.archive');
        Route::patch('locations/communities/archived/{record}', fn () => null)->name('communities.archived.activate');
        Route::patch('locations/communities/trash/{record}', fn () => null)->name('communities.trash.restore');
        Route::delete('locations/communities/trash/{record}', fn () => null)->name('communities.trash.destroy');
    }

    /**
     * A community without parents: the unit path force-fills the attributes it needs, without the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): Community
    {
        $record = (new Community)->forceFill([...$attributes, 'id' => $attributes['id'] ?? 1]);

        $record->setAttribute('countries_count', $attributes['countries_count'] ?? 0);

        return $record;
    }

    public function test_confirmation_names_the_community_by_its_name(): void
    {
        $rows = (new CommunityListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Euskadi'])], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['community-delete-1'];

        $this->assertSame(
            __('basics13::messages.delete_record', ['name' => 'Euskadi']),
            $delete['confirmTitle'],
        );
    }

    public function test_a_community_children_point_at_offers_archive_instead_of_the_delete(): void
    {
        $rows = (new CommunityListTransformer)->active(new LengthAwarePaginator([$this->record(['name' => 'Euskadi', 'countries_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'], null, 'test');

        $this->assertArrayHasKey('community-archive-1', $actions);
        $this->assertArrayNotHasKey('community-delete-1', $actions);
    }

    public function test_a_trashed_community_children_point_at_cannot_be_force_deleted(): void
    {
        $rows = (new CommunityListTransformer)->trash(new LengthAwarePaginator([$this->record(['name' => 'Euskadi', 'countries_count' => 1])], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayNotHasKey('community-force-delete-1', $actions);
    }

    public function test_the_edit_payload_carries_the_fields_the_form_edits(): void
    {
        $rows = (new CommunityListTransformer)->active(new LengthAwarePaginator(
            [$this->record(['name' => 'Euskadi', 'ine_code' => '01'])],
            1,
            15,
        ), '')['rows'];

        $this->assertSame([
            'id' => 1,
            'name' => 'Euskadi',
            'ine_code' => '01',
            'notes' => null,
            'state_id' => null,
            'state_label' => null,
            'state_active' => null,
        ], $rows[0]['editPayload']);
    }

    public function test_a_trashed_community_without_children_may_be_force_deleted(): void
    {
        $record = $this->record(['name' => 'Euskadi']);
        $record->setAttribute('active', false);

        $rows = (new CommunityListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $actions = array_column($rows[0]['actions'] ?? [], null, 'test');

        $this->assertArrayHasKey('community-force-delete-1', $actions);
    }
}
