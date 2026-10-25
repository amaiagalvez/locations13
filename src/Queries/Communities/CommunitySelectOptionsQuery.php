<?php

namespace Locations13\Queries\Communities;

use Locations13\Models\Community;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;

/**
 * @extends ListQueryBase<Community>
 */
final class CommunitySelectOptionsQuery extends ListQueryBase
{
    /**
     * Options for the forms that hold a community. The term the user types is matched against the
     * name, so a community can be found by the name it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = Community::query()
            ->where('LOC_communities.active', true)
            ->orderBy('LOC_communities.name')
            ->orderBy('LOC_communities.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->whereRaw('LOC_communities.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (Community $community): array => [
                'id' => $community->id,
                'text' => $community->name,
            ]);
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
     * Select-option queries do not expose list filters.
     *
     * @return array{}
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }
}
