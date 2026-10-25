<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Community;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Locations13\Http\Requests\CommunityListRequest;
use Locations13\Http\Requests\CommunityDestroyRequest;
use Locations13\Http\Requests\CommunityRestoreRequest;
use Locations13\Transformers\CommunityListTransformer;
use Locations13\Queries\Communities\CommunityListQuery;

/**
 * @extends TrashController<Community>
 */
class CommunityTrashController extends TrashController
{
    public function index(
        CommunityListRequest $request,
        CommunityListQuery $query,
        CommunityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $communities = $query->trashed($search);

        return $this->listView($request, 'locations13::communities.list', [
            'communities' => $communities,
            'list' => $transformer->trash(
                $communities,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $communities->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(CommunityRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(CommunityDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Community  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A community name must be free among the communities that are not in the trash.
     *
     * @param  Community  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(Community::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return Community::class;
    }

    protected function trashRoute(): string
    {
        return 'communities.trash.index';
    }
}
