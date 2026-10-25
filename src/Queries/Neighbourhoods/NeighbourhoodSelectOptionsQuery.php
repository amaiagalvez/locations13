<?php

namespace Locations13\Queries\Neighbourhoods;

use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Locations13\Models\Neighbourhood;

/**
 * @extends ListQueryBase<Neighbourhood>
 */
final class NeighbourhoodSelectOptionsQuery extends ListQueryBase
{
    /**
     * Options for the forms that need a neighbourhood select.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = Neighbourhood::query()
            ->where('LOC_neighbourhoods.active', true)
            ->orderBy('LOC_neighbourhoods.name')
            ->orderBy('LOC_neighbourhoods.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->whereRaw('LOC_neighbourhoods.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (Neighbourhood $neighbourhood): array => [
                'id' => $neighbourhood->id,
                'text' => $neighbourhood->name,
            ]);
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
