<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Locations13\Models\Neighbourhood;
use Basics13\Http\Controllers\ArchivedController;
use Locations13\Http\Requests\NeighbourhoodListRequest;
use Locations13\Transformers\NeighbourhoodListTransformer;
use Locations13\Queries\Neighbourhoods\NeighbourhoodListQuery;

class NeighbourhoodArchivedController extends ArchivedController
{
    public function index(
        NeighbourhoodListRequest $request,
        NeighbourhoodListQuery $query,
        NeighbourhoodListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $neighbourhoods = $query->archived($search);

        return $this->listView($request, 'locations13::neighbourhoods.list', [
            'neighbourhoods' => $neighbourhoods,
            'list' => $transformer->archived(
                $neighbourhoods,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $neighbourhoods->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(Neighbourhood $neighbourhood): RedirectResponse
    {
        return $this->archiveRecord($neighbourhood);
    }

    public function activate(Neighbourhood $neighbourhood): RedirectResponse
    {
        return $this->activateRecord($neighbourhood);
    }

    protected function activeRoute(): string
    {
        return 'neighbourhoods.index';
    }

    protected function archivedRoute(): string
    {
        return 'neighbourhoods.archived.index';
    }
}
