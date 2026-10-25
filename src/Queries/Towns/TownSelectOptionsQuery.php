<?php

namespace Locations13\Queries\Towns;

use Locations13\Models\Town;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;

/**
 * @extends ListQueryBase<Town>
 */
final class TownSelectOptionsQuery extends ListQueryBase
{
    /**
     * Options for the neighbourhood form's town select.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = Town::query()
            ->where('LOC_towns.active', true)
            ->orderBy('LOC_towns.name')
            ->orderBy('LOC_towns.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->whereRaw('LOC_towns.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (Town $town): array => [
                'id' => $town->id,
                'text' => $town->name,
            ]);
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
     * Select-option queries do not expose list filters.
     *
     * @return array{}
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }
}
