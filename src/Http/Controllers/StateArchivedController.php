<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\State;
use Illuminate\Http\RedirectResponse;
use Locations13\Queries\States\StateListQuery;
use Locations13\Http\Requests\StateListRequest;
use Basics13\Http\Controllers\ArchivedController;
use Locations13\Transformers\StateListTransformer;

class StateArchivedController extends ArchivedController
{
    public function index(
        StateListRequest $request,
        StateListQuery $query,
        StateListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $states = $query->archived($search);

        return $this->listView($request, 'locations13::states.list', [
            'states' => $states,
            'list' => $transformer->archived(
                $states,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $states->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(State $state): RedirectResponse
    {
        return $this->archiveRecord($state);
    }

    public function activate(State $state): RedirectResponse
    {
        return $this->activateRecord($state);
    }

    protected function activeRoute(): string
    {
        return 'states.index';
    }

    protected function archivedRoute(): string
    {
        return 'states.archived.index';
    }
}
