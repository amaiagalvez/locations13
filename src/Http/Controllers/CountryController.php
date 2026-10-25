<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Locations13\Http\Requests\CountryRequest;
use Locations13\Http\Requests\CountryListRequest;
use Locations13\Queries\Countries\CountryListQuery;
use Locations13\Transformers\CountryListTransformer;
use Basics13\Support\Database\UniqueConstraintViolation;
use Locations13\Http\Requests\CountrySelectOptionsRequest;
use Locations13\Queries\Countries\CountrySelectOptionsQuery;

class CountryController extends Controller
{
    public function index(
        CountryListRequest $request,
        CountryListQuery $query,
        CountryListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $countries = $query->active($search);

        return $this->listView($request, 'locations13::countries.list', [
            'countries' => $countries,
            'list' => $transformer->active(
                $countries,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $countries->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        CountrySelectOptionsRequest $request,
        CountrySelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(CountryRequest $request, CountryListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedCountry = $query->findTrashedByName($name);

        if ($deletedCountry !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedCountry, 'countries.index', 'deleted_country_conflict');
        }

        try {
            $country = Country::create($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($country->only(['id', 'name']), 201);
        }

        return $this->backToList('countries.index')->with('status', __('basics13::messages.created'));
    }

    public function update(CountryRequest $request, Country $country): RedirectResponse
    {
        try {
            $country->update($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return $this->backToList('countries.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(Country $country): RedirectResponse
    {
        $this->authorize('delete', $country);

        // The deleting hook holds the row lock and refuses the delete while any record of the lower
        // levels points at the country, so the controller only has to run it inside a transaction
        // and report the outcome.
        $deleted = DB::transaction(static fn (): bool => $country->delete() !== false);

        if (! $deleted) {
            return $this->backToList('countries.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return $this->backToList('countries.index')->with('status', __('basics13::messages.moved_to_trash'));
    }
}
