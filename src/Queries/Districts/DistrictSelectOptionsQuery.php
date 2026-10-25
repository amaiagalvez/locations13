<?php

namespace Locations13\Queries\Districts;

use Locations13\Models\District;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;

/**
 * @extends ListQueryBase<District>
 */
final class DistrictSelectOptionsQuery extends ListQueryBase
{
    /**
     * Options for the town form's comarca select.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = District::query()
            ->where('LOC_districts.active', true)
            ->orderBy('LOC_districts.name')
            ->orderBy('LOC_districts.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->whereRaw('LOC_districts.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (District $district): array => [
                'id' => $district->id,
                'text' => $district->name,
            ]);
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
     * Select-option queries do not expose list filters.
     *
     * @return array{}
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }
}
