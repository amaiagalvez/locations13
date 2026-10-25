<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Locations13\Models\Neighbourhood;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Locations13\Http\Requests\NeighbourhoodRequest;
use Locations13\Http\Requests\NeighbourhoodListRequest;
use Basics13\Support\Database\UniqueConstraintViolation;
use Locations13\Transformers\NeighbourhoodListTransformer;
use Locations13\Queries\Neighbourhoods\NeighbourhoodListQuery;
use Locations13\Http\Requests\NeighbourhoodSelectOptionsRequest;
use Locations13\Queries\Neighbourhoods\NeighbourhoodSelectOptionsQuery;

class NeighbourhoodController extends Controller
{
    public function index(
        NeighbourhoodListRequest $request,
        NeighbourhoodListQuery $query,
        NeighbourhoodListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $neighbourhoods = $query->active($search);

        return $this->listView($request, 'locations13::neighbourhoods.list', [
            'neighbourhoods' => $neighbourhoods,
            'list' => $transformer->active(
                $neighbourhoods,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $neighbourhoods->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        NeighbourhoodSelectOptionsRequest $request,
        NeighbourhoodSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(NeighbourhoodRequest $request, NeighbourhoodListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedNeighbourhood = $query->findTrashedByName($name, $request->integer('town_id'));

        if ($deletedNeighbourhood !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedNeighbourhood, 'neighbourhoods.index', 'deleted_neighbourhood_conflict');
        }

        try {
            $neighbourhood = Neighbourhood::create($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($neighbourhood->only(['id', 'name']), 201);
        }

        return $this->backToList('neighbourhoods.index')->with('status', __('basics13::messages.created'));
    }

    public function update(NeighbourhoodRequest $request, Neighbourhood $neighbourhood): RedirectResponse
    {
        try {
            $neighbourhood->update($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return $this->backToList('neighbourhoods.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(Neighbourhood $neighbourhood): RedirectResponse
    {
        $this->authorize('delete', $neighbourhood);

        $deleted = DB::transaction(static fn (): bool => $neighbourhood->delete() !== false);

        if (! $deleted) {
            return $this->backToList('neighbourhoods.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return $this->backToList('neighbourhoods.index')->with('status', __('basics13::messages.moved_to_trash'));
    }
}
