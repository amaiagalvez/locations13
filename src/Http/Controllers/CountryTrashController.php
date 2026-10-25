<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Locations13\Http\Requests\CountryListRequest;
use Locations13\Queries\Countries\CountryListQuery;
use Locations13\Http\Requests\CountryDestroyRequest;
use Locations13\Http\Requests\CountryRestoreRequest;
use Locations13\Transformers\CountryListTransformer;

/**
 * @extends TrashController<Country>
 */
class CountryTrashController extends TrashController
{
    public function index(
        CountryListRequest $request,
        CountryListQuery $query,
        CountryListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $countries = $query->trashed($search);

        return $this->listView($request, 'locations13::countries.list', [
            'countries' => $countries,
            'list' => $transformer->trash(
                $countries,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $countries->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(CountryRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(CountryDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Country  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A country name must be free among the records that are not in the trash, so it cannot be put
     * back when a record nobody deleted took the same name in between.
     *
     * @param  Country  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(Country::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return Country::class;
    }

    protected function trashRoute(): string
    {
        return 'countries.trash.index';
    }
}
