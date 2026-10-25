<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Locations13\Http\Requests\DistrictRequest;
use Locations13\Http\Requests\DistrictListRequest;
use Locations13\Queries\Districts\DistrictListQuery;
use Locations13\Transformers\DistrictListTransformer;
use Basics13\Support\Database\UniqueConstraintViolation;
use Locations13\Http\Requests\DistrictSelectOptionsRequest;
use Locations13\Queries\Districts\DistrictSelectOptionsQuery;

class DistrictController extends Controller
{
    public function index(
        DistrictListRequest $request,
        DistrictListQuery $query,
        DistrictListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $districts = $query->active($search);

        return $this->listView($request, 'locations13::districts.list', [
            'districts' => $districts,
            'list' => $transformer->active(
                $districts,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $districts->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        DistrictSelectOptionsRequest $request,
        DistrictSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(DistrictRequest $request, DistrictListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedDistrict = $query->findTrashedByName($name);

        if ($deletedDistrict !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedDistrict, 'districts.index', 'deleted_district_conflict');
        }

        try {
            $district = District::create($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($district->only(['id', 'name']), 201);
        }

        return $this->backToList('districts.index')->with('status', __('basics13::messages.created'));
    }

    public function update(DistrictRequest $request, District $district): RedirectResponse
    {
        try {
            $district->update($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return $this->backToList('districts.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(District $district): RedirectResponse
    {
        $this->authorize('delete', $district);

        // The deleting hook holds the row lock and refuses the delete while a town names the
        // comarca, so the controller only has to run it inside a transaction and report the outcome.
        $deleted = DB::transaction(static fn (): bool => $district->delete() !== false);

        if (! $deleted) {
            return $this->backToList('districts.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return $this->backToList('districts.index')->with('status', __('basics13::messages.moved_to_trash'));
    }
}
