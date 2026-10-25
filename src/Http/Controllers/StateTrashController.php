<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Locations13\Queries\States\StateListQuery;
use Locations13\Http\Requests\StateListRequest;
use Locations13\Http\Requests\StateDestroyRequest;
use Locations13\Http\Requests\StateRestoreRequest;
use Locations13\Transformers\StateListTransformer;

/**
 * @extends TrashController<State>
 */
class StateTrashController extends TrashController
{
    public function index(
        StateListRequest $request,
        StateListQuery $query,
        StateListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $states = $query->trashed($search);

        return $this->listView($request, 'locations13::states.list', [
            'states' => $states,
            'list' => $transformer->trash(
                $states,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $states->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(StateRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(StateDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  State  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A state name must be free among the states that are not in the trash.
     *
     * @param  State  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(State::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return State::class;
    }

    protected function trashRoute(): string
    {
        return 'states.trash.index';
    }
}
