<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Town;
use Illuminate\Http\RedirectResponse;
use Locations13\Queries\Towns\TownListQuery;
use Locations13\Http\Requests\TownListRequest;
use Basics13\Http\Controllers\ArchivedController;
use Locations13\Transformers\TownListTransformer;

class TownArchivedController extends ArchivedController
{
    public function index(
        TownListRequest $request,
        TownListQuery $query,
        TownListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $towns = $query->archived($search);

        return $this->listView($request, 'locations13::towns.list', [
            'towns' => $towns,
            'list' => $transformer->archived(
                $towns,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $towns->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(Town $town): RedirectResponse
    {
        return $this->archiveRecord($town);
    }

    public function activate(Town $town): RedirectResponse
    {
        return $this->activateRecord($town);
    }

    protected function activeRoute(): string
    {
        return 'towns.index';
    }

    protected function archivedRoute(): string
    {
        return 'towns.archived.index';
    }
}
