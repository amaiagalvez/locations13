<?php

namespace Locations13\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Locations13\Queries\Neighbourhoods\NeighbourhoodListQuery;

class NeighbourhoodListQueryTest extends TestCase
{
    public function test_query_can_filter_by_state(): void
    {
        foreach (['active', 'archived', 'trashed'] as $method) {
            $this->assertTrue((new \ReflectionMethod(NeighbourhoodListQuery::class, $method))->isPublic());
        }
    }

    public function test_query_can_find_trashed_by_name(): void
    {
        $this->assertTrue((new \ReflectionMethod(NeighbourhoodListQuery::class, 'findTrashedByName'))->isPublic());
    }

    public function test_state_counts_returns_array_with_expected_keys(): void
    {
        $query = new NeighbourhoodListQuery;

        $this->assertSame(
            ['active' => 10, 'archived' => 5, 'trashed' => 3],
            $query->stateCounts(10, 5, 3),
        );
    }

    public function test_the_list_offers_no_advanced_filters_in_any_state(): void
    {
        $query = new NeighbourhoodListQuery;

        foreach (['active', 'archived', 'trashed'] as $scope) {
            $this->assertSame([], $query->searchableFields($scope));
        }
    }

    public function test_the_search_columns_are_the_name_and_the_reference_code(): void
    {
        $columns = (new \ReflectionClass(NeighbourhoodListQuery::class))->getConstant('SEARCH_COLUMNS');

        $this->assertSame(['LOC_neighbourhoods.name', 'LOC_neighbourhoods.ine_code'], $columns);
    }

    public function test_the_trashed_lookup_is_scoped_by_the_town(): void
    {
        $method = new \ReflectionMethod(NeighbourhoodListQuery::class, 'findTrashedByName');
        $town = $method->getParameters()[1];

        $this->assertSame(NeighbourhoodListQuery::class, $method->getDeclaringClass()->getName());
        $this->assertSame('townId', $town->getName());
        $this->assertTrue($town->allowsNull());
        $this->assertNull($town->getDefaultValue());
    }
}
