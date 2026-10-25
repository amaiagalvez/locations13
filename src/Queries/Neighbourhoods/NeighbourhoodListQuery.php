<?php

namespace Locations13\Queries\Neighbourhoods;

use Basics13\Queries\ListQueryBase;
use Locations13\Models\Neighbourhood;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends ListQueryBase<Neighbourhood>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class NeighbourhoodListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['LOC_neighbourhoods.name', 'LOC_neighbourhoods.ine_code'];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Neighbourhood>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            Neighbourhood::query()->with(['town:id,name,active'])->where('LOC_neighbourhoods.active', true)->orderBy('LOC_neighbourhoods.name')->orderBy('LOC_neighbourhoods.id'),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Neighbourhood>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            Neighbourhood::query()->with(['town:id,name,active'])->where('LOC_neighbourhoods.active', false)->orderBy('LOC_neighbourhoods.name')->orderBy('LOC_neighbourhoods.id'),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_neighbourhoods.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Neighbourhood>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            Neighbourhood::onlyTrashed()->with(['town:id,name,active'])->latest('deleted_at')->orderBy('id'),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_neighbourhoods.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * Neighbourhoods are a short catalogue: the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * Neighbourhood names are unique inside their town, so the trashed one a new one would collide
     * with is looked up inside the neighbourhood's own town.
     */
    public function findTrashedByName(string $name, ?int $townId = null): ?Model
    {
        $query = $this->trashedQuery();

        if ($townId !== null && $townId > 0) {
            $query->where('town_id', $townId);
        }

        return $this->firstTrashedByName($query, $name);
    }

    /**
     * @return class-string<Neighbourhood>
     */
    protected function getModelClass(): string
    {
        return Neighbourhood::class;
    }

    protected function integration(): object
    {
        return new class
        {
            /**
             * @param  non-empty-list<literal-string>  $columns
             * @return non-empty-list<literal-string>
             */
            public function searchColumns(string $scope, array $columns): array
            {
                return $columns;
            }
        };
    }
}
