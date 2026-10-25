<?php

namespace Locations13\Queries\Countries;

use Locations13\Models\Country;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<Country>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class CountryListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['LOC_countries.name', 'LOC_countries.ine_code'];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Country>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Country::query()->where('LOC_countries.active', true)->orderBy('LOC_countries.name')->orderBy('LOC_countries.id')),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Country>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Country::query()->where('LOC_countries.active', false)->orderBy('LOC_countries.name')->orderBy('LOC_countries.id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_countries.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Country>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Country::onlyTrashed()->latest('deleted_at')->orderBy('id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_countries.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * Countries are a short catalogue: the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * @return class-string<Country>
     */
    protected function getModelClass(): string
    {
        return Country::class;
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
     * How many records of the lower levels name the herrialde, deleted ones included.
     *
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    private function listed(Builder $query): Builder
    {
        return $query->with(['community:id,name,active'])->withCount([
            'districts' => fn (Builder $districts): Builder => $districts->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
