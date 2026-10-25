<?php

namespace Locations13\Queries\Towns;

use Locations13\Models\Town;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<Town>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class TownListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['LOC_towns.name', 'LOC_towns.ine_code'];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Town>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Town::query()->where('LOC_towns.active', true)->orderBy('LOC_towns.name')->orderBy('LOC_towns.id')),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Town>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Town::query()->where('LOC_towns.active', false)->orderBy('LOC_towns.name')->orderBy('LOC_towns.id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_towns.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Town>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Town::onlyTrashed()->latest('deleted_at')->orderBy('id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_towns.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * Towns are a short catalogue: the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * @return class-string<Town>
     */
    protected function getModelClass(): string
    {
        return Town::class;
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

    /**
     * How many neighbourhoods name the town, deleted ones included, and the postal codes the list
     * shows as the row says.
     *
     * @param  Builder<Town>  $query
     * @return Builder<Town>
     */
    private function listed(Builder $query): Builder
    {
        return $query->with(['district:id,name,active', 'postalCodes:id,town_id,code'])->withCount([
            'neighbourhoods' => fn (Builder $neighbourhoods): Builder => $neighbourhoods->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
