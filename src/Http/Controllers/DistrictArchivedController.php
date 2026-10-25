<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\District;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Locations13\Http\Requests\DistrictListRequest;
use Locations13\Queries\Districts\DistrictListQuery;
use Locations13\Transformers\DistrictListTransformer;

class DistrictArchivedController extends ArchivedController
{
    public function index(
        DistrictListRequest $request,
        DistrictListQuery $query,
        DistrictListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $districts = $query->archived($search);

        return $this->listView($request, 'locations13::districts.list', [
            'districts' => $districts,
            'list' => $transformer->archived(
                $districts,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $districts->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(District $district): RedirectResponse
    {
        return $this->archiveRecord($district);
    }

    public function activate(District $district): RedirectResponse
    {
        return $this->activateRecord($district);
    }

    protected function activeRoute(): string
    {
        return 'districts.index';
    }

    protected function archivedRoute(): string
    {
        return 'districts.archived.index';
    }
}
