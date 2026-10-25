<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Locations13\Models\Neighbourhood;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Locations13\Http\Requests\NeighbourhoodListRequest;
use Locations13\Http\Requests\NeighbourhoodDestroyRequest;
use Locations13\Http\Requests\NeighbourhoodRestoreRequest;
use Locations13\Transformers\NeighbourhoodListTransformer;
use Locations13\Queries\Neighbourhoods\NeighbourhoodListQuery;

/**
 * @extends TrashController<Neighbourhood>
 */
class NeighbourhoodTrashController extends TrashController
{
    public function index(
        NeighbourhoodListRequest $request,
        NeighbourhoodListQuery $query,
        NeighbourhoodListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $neighbourhoods = $query->trashed($search);

        return $this->listView($request, 'locations13::neighbourhoods.list', [
            'neighbourhoods' => $neighbourhoods,
            'list' => $transformer->trash(
                $neighbourhoods,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $neighbourhoods->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(NeighbourhoodRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(NeighbourhoodDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Neighbourhood  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * Neighbourhood names are only unique inside their town, so the search is narrowed to it.
     *
     * @param  Neighbourhood  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(
            Neighbourhood::query()->where('town_id', $record->town_id),
            $record->name,
        );
    }

    protected function recordClass(): string
    {
        return Neighbourhood::class;
    }

    protected function trashRoute(): string
    {
        return 'neighbourhoods.trash.index';
    }
}
