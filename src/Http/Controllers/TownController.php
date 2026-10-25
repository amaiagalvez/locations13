<?php

namespace Locations13\Http\Controllers;

use Illuminate\View\View;
use Locations13\Models\Town;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Locations13\Http\Requests\TownRequest;
use Locations13\Queries\Towns\TownListQuery;
use Locations13\Http\Requests\TownListRequest;
use Locations13\Transformers\TownListTransformer;
use Locations13\Queries\Towns\TownSelectOptionsQuery;
use Locations13\Http\Requests\TownSelectOptionsRequest;
use Basics13\Support\Database\UniqueConstraintViolation;

class TownController extends Controller
{
    public function index(
        TownListRequest $request,
        TownListQuery $query,
        TownListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $towns = $query->active($search);

        return $this->listView($request, 'locations13::towns.list', [
            'towns' => $towns,
            'list' => $transformer->active(
                $towns,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $towns->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        TownSelectOptionsRequest $request,
        TownSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(TownRequest $request, TownListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedTown = $query->findTrashedByName($name);

        if ($deletedTown !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedTown, 'towns.index', 'deleted_town_conflict');
        }

        try {
            // The town and its postal codes are written in one transaction: a town is saved with
            // the codes of its form or with neither.
            $town = DB::transaction(static function () use ($request): Town {
                $town = Town::create($request->safe()->except(['postal_codes']));
                self::syncPostalCodes($town, $request->validated()['postal_codes'] ?? null);

                return $town;
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($town->only(['id', 'name']), 201);
        }

        return $this->backToList('towns.index')->with('status', __('basics13::messages.created'));
    }

    public function update(TownRequest $request, Town $town): RedirectResponse
    {
        try {
            DB::transaction(static function () use ($request, $town): void {
                $town->update($request->safe()->except(['postal_codes']));
                self::syncPostalCodes($town, $request->validated()['postal_codes'] ?? null);
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return $this->backToList('towns.index')->with('status', __('basics13::messages.updated'));
    }

    /**
     * The codes of the form replace the town's ones: the town is saved with what the form holds.
     * When the request carries no postal codes at all, the town keeps what it has, so a caller
     * that edits other fields never erases the codes.
     *
     * @param  list<string>|null  $codes
     */
    private static function syncPostalCodes(Town $town, ?array $codes): void
    {
        if ($codes === null) {
            return;
        }

        $current = $town->postalCodes()->pluck('code')->all();

        // A code the form drops is removed for good: a trashed one would come back with the town's restore.
        $town->postalCodes()->whereNotIn('code', $codes)->forceDelete();

        foreach (array_diff($codes, $current) as $code) {
            $town->postalCodes()->create(['code' => $code]);
        }
    }

    public function destroy(Town $town): RedirectResponse
    {
        $this->authorize('delete', $town);

        // The deleting hook holds the row lock and refuses the delete while a neighbourhood names
        // the town; the postal codes are not a reason and vanish with the town.
        $deleted = DB::transaction(static fn (): bool => $town->delete() !== false);

        if (! $deleted) {
            return $this->backToList('towns.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return $this->backToList('towns.index')->with('status', __('basics13::messages.moved_to_trash'));
    }
}
