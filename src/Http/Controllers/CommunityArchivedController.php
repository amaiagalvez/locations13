<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Community;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Locations13\Http\Requests\CommunityListRequest;
use Locations13\Transformers\CommunityListTransformer;
use Locations13\Queries\Communities\CommunityListQuery;

class CommunityArchivedController extends ArchivedController
{
    public function index(
        CommunityListRequest $request,
        CommunityListQuery $query,
        CommunityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $communities = $query->archived($search);

        return $this->listView($request, 'locations13::communities.list', [
            'communities' => $communities,
            'list' => $transformer->archived(
                $communities,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $communities->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(Community $community): RedirectResponse
    {
        return $this->archiveRecord($community);
    }

    public function activate(Community $community): RedirectResponse
    {
        return $this->activateRecord($community);
    }

    protected function activeRoute(): string
    {
        return 'communities.index';
    }

    protected function archivedRoute(): string
    {
        return 'communities.archived.index';
    }
}
