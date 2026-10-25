<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Town;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Locations13\Queries\Towns\TownListQuery;
use Basics13\Http\Controllers\TrashController;
use Locations13\Http\Requests\TownListRequest;
use Locations13\Http\Requests\TownDestroyRequest;
use Locations13\Http\Requests\TownRestoreRequest;
use Locations13\Transformers\TownListTransformer;

/**
 * @extends TrashController<Town>
 */
class TownTrashController extends TrashController
{
    public function index(
        TownListRequest $request,
        TownListQuery $query,
        TownListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $towns = $query->trashed($search);

        return $this->listView($request, 'locations13::towns.list', [
            'towns' => $towns,
            'list' => $transformer->trash(
                $towns,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $towns->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(TownRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(TownDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Town  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A town name must be free among the towns that are not in the trash.
     *
     * @param  Town  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(Town::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return Town::class;
    }

    protected function trashRoute(): string
    {
        return 'towns.trash.index';
    }
}
