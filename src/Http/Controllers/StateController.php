<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Locations13\Http\Requests\StateRequest;
use Locations13\Queries\States\StateListQuery;
use Locations13\Http\Requests\StateListRequest;
use Locations13\Transformers\StateListTransformer;
use Locations13\Queries\States\StateSelectOptionsQuery;
use Basics13\Support\Database\UniqueConstraintViolation;
use Locations13\Http\Requests\StateSelectOptionsRequest;

class StateController extends Controller
{
    public function index(
        StateListRequest $request,
        StateListQuery $query,
        StateListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $states = $query->active($search);

        return $this->listView($request, 'locations13::states.list', [
            'states' => $states,
            'list' => $transformer->active(
                $states,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $states->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        StateSelectOptionsRequest $request,
        StateSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(StateRequest $request, StateListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedState = $query->findTrashedByName($name);

        if ($deletedState !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedState, 'states.index', 'deleted_state_conflict');
        }

        try {
            $state = State::create($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($state->only(['id', 'name']), 201);
        }

        return $this->backToList('states.index')->with('status', __('basics13::messages.created'));
    }

    public function update(StateRequest $request, State $state): RedirectResponse
    {
        try {
            $state->update($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return $this->backToList('states.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(State $state): RedirectResponse
    {
        $this->authorize('delete', $state);

        // The deleting hook holds the row lock and refuses the delete while any record of the lower
        // levels names the state, so the controller only has to run it inside a transaction and
        // report the outcome.
        $deleted = DB::transaction(static fn (): bool => $state->delete() !== false);

        if (! $deleted) {
            return $this->backToList('states.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return $this->backToList('states.index')->with('status', __('basics13::messages.moved_to_trash'));
    }
}
