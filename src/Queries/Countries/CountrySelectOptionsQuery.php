<?php

namespace Locations13\Queries\Countries;

use Locations13\Models\Country;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;

/**
 * @extends ListQueryBase<Country>
 */
final class CountrySelectOptionsQuery extends ListQueryBase
{
    /**
     * Options for the forms of the lower levels. The term the user types is matched against the
     * name, so a country can be found by the name it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = Country::query()
            ->where('LOC_countries.active', true)
            ->orderBy('LOC_countries.name')
            ->orderBy('LOC_countries.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->whereRaw('LOC_countries.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (Country $country): array => [
                'id' => $country->id,
                'text' => $country->name,
            ]);
    }

    /**
     * @return class-string<Country>
     */
    protected function getModelClass(): string
    {
        return Country::class;
    }

    /**
     * Select options have no integration, so the base hook gets a no-op that appends nothing.
     */
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
