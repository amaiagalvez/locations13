<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Locations13\Models\Community;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Locations13\Http\Requests\CommunityRequest;
use Locations13\Http\Requests\CommunityListRequest;
use Locations13\Transformers\CommunityListTransformer;
use Locations13\Queries\Communities\CommunityListQuery;
use Basics13\Support\Database\UniqueConstraintViolation;
use Locations13\Http\Requests\CommunitySelectOptionsRequest;
use Locations13\Queries\Communities\CommunitySelectOptionsQuery;

class CommunityController extends Controller
{
    public function index(
        CommunityListRequest $request,
        CommunityListQuery $query,
        CommunityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $communities = $query->active($search);

        return $this->listView($request, 'locations13::communities.list', [
            'communities' => $communities,
            'list' => $transformer->active(
                $communities,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $communities->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        CommunitySelectOptionsRequest $request,
        CommunitySelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(CommunityRequest $request, CommunityListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedCommunity = $query->findTrashedByName($name);

        if ($deletedCommunity !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedCommunity, 'communities.index', 'deleted_community_conflict');
        }

        try {
            $community = Community::create($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($community->only(['id', 'name']), 201);
        }

        return $this->backToList('communities.index')->with('status', __('basics13::messages.created'));
    }

    public function update(CommunityRequest $request, Community $community): RedirectResponse
    {
        try {
            $community->update($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return $this->backToList('communities.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(Community $community): RedirectResponse
    {
        $this->authorize('delete', $community);

        $deleted = DB::transaction(static fn (): bool => $community->delete() !== false);

        if (! $deleted) {
            return $this->backToList('communities.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return $this->backToList('communities.index')->with('status', __('basics13::messages.moved_to_trash'));
    }
}
