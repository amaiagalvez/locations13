<?php

namespace Locations13\Queries\Districts;

use Locations13\Models\District;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<District>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class DistrictListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['LOC_districts.name', 'LOC_districts.ine_code'];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, District>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(District::query()->where('LOC_districts.active', true)->orderBy('LOC_districts.name')->orderBy('LOC_districts.id')),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, District>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(District::query()->where('LOC_districts.active', false)->orderBy('LOC_districts.name')->orderBy('LOC_districts.id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_districts.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, District>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(District::onlyTrashed()->latest('deleted_at')->orderBy('id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_districts.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * Districts are a short catalogue: the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * @return class-string<District>
     */
    protected function getModelClass(): string
    {
        return District::class;
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
     * How many towns name the comarca, deleted ones included.
     *
     * @param  Builder<District>  $query
     * @return Builder<District>
     */
    private function listed(Builder $query): Builder
    {
        return $query->with(['country:id,name,active'])->withCount([
            'towns' => fn (Builder $towns): Builder => $towns->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
