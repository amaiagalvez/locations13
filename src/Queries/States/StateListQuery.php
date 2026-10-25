<?php

namespace Locations13\Queries\States;

use Locations13\Models\State;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<State>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class StateListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['LOC_states.name', 'LOC_states.ine_code'];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, State>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(State::query()->where('LOC_states.active', true)->orderBy('LOC_states.name')->orderBy('LOC_states.id'), true),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, State>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(State::query()->where('LOC_states.active', false)->orderBy('LOC_states.name')->orderBy('LOC_states.id'), true),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_states.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, State>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(State::onlyTrashed()->latest('deleted_at')->orderBy('id'), false),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'LOC_states.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * States are a short catalogue: the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * @return class-string<State>
     */
    protected function getModelClass(): string
    {
        return State::class;
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
     * How many communities name the state, deleted ones included.
     * Only used for active/archived scopes where the delete/archive action is shown.
     *
     * @param  Builder<State>  $query
     * @return Builder<State>
     */
    private function listed(Builder $query, bool $withCount): Builder
    {
        if (! $withCount) {
            return $query;
        }

        return $query->withCount([
            'communities' => fn (Builder $communities): Builder => $communities->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
