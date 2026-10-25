<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Locations13\Http\Requests\DistrictListRequest;
use Locations13\Queries\Districts\DistrictListQuery;
use Locations13\Http\Requests\DistrictDestroyRequest;
use Locations13\Http\Requests\DistrictRestoreRequest;
use Locations13\Transformers\DistrictListTransformer;

/**
 * @extends TrashController<District>
 */
class DistrictTrashController extends TrashController
{
    public function index(
        DistrictListRequest $request,
        DistrictListQuery $query,
        DistrictListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $districts = $query->trashed($search);

        return $this->listView($request, 'locations13::districts.list', [
            'districts' => $districts,
            'list' => $transformer->trash(
                $districts,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $districts->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(DistrictRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(DistrictDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  District  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A comarca name must be free among the districts that are not in the trash.
     *
     * @param  District  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(District::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return District::class;
    }

    protected function trashRoute(): string
    {
        return 'districts.trash.index';
    }
}
