<?php

namespace Locations13\Queries\Communities;

use Locations13\Models\Community;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<Community>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class CommunityListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['LOC_communities.name', 'LOC_communities.ine_code'];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Community>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Community::query()->where('LOC_communities.active', true)->orderBy('LOC_communities.name')->orderBy('LOC_communities.id')),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Community>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Community::query()->where('LOC_communities.active', false)->orderBy('LOC_communities.name')->orderBy('LOC_communities.id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_communities.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Community>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Community::onlyTrashed()->latest('deleted_at')->orderBy('id')),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_communities.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * Communities are a short catalogue: the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * @return class-string<Community>
     */
    protected function getModelClass(): string
    {
        return Community::class;
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
     * How many herrialdeak name the community, deleted ones included.
     *
     * @param  Builder<Community>  $query
     * @return Builder<Community>
     */
    private function listed(Builder $query): Builder
    {
        return $query->with(['state:id,name,active'])->withCount([
            'countries' => fn (Builder $countries): Builder => $countries->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
