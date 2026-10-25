<?php

namespace Locations13\Queries\States;

use Locations13\Models\State;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;

/**
 * @extends ListQueryBase<State>
 */
final class StateSelectOptionsQuery extends ListQueryBase
{
    /**
     * Options for the forms of the lower levels. The term the user types is matched against the
     * name, so a state can be found by the name it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = State::query()
            ->where('LOC_states.active', true)
            ->orderBy('LOC_states.name')
            ->orderBy('LOC_states.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->whereRaw('LOC_states.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (State $state): array => [
                'id' => $state->id,
                'text' => $state->name,
            ]);
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
     * Select-option queries do not expose list filters.
     *
     * @return array{}
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }
}
