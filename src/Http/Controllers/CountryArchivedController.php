<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Country;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Locations13\Http\Requests\CountryListRequest;
use Locations13\Queries\Countries\CountryListQuery;
use Locations13\Transformers\CountryListTransformer;

class CountryArchivedController extends ArchivedController
{
    public function index(
        CountryListRequest $request,
        CountryListQuery $query,
        CountryListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $countries = $query->archived($search);

        return $this->listView($request, 'locations13::countries.list', [
            'countries' => $countries,
            'list' => $transformer->archived(
                $countries,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $countries->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(Country $country): RedirectResponse
    {
        return $this->archiveRecord($country);
    }

    public function activate(Country $country): RedirectResponse
    {
        return $this->activateRecord($country);
    }

    protected function activeRoute(): string
    {
        return 'countries.index';
    }

    protected function archivedRoute(): string
    {
        return 'countries.archived.index';
    }
}
