<?php

namespace Locations13\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Locations13\Queries\Towns\TownListQuery;

class TownListQueryTest extends TestCase
{
    public function test_query_can_filter_by_state(): void
    {
        foreach (['active', 'archived', 'trashed'] as $method) {
            $this->assertTrue((new \ReflectionMethod(TownListQuery::class, $method))->isPublic());
        }
    }

    public function test_query_can_find_trashed_by_name(): void
    {
        $this->assertTrue((new \ReflectionMethod(TownListQuery::class, 'findTrashedByName'))->isPublic());
    }

    public function test_state_counts_returns_array_with_expected_keys(): void
    {
        $query = new TownListQuery;

        $this->assertSame(
            ['active' => 10, 'archived' => 5, 'trashed' => 3],
            $query->stateCounts(10, 5, 3),
        );
    }

    public function test_the_list_offers_no_advanced_filters_in_any_state(): void
    {
        $query = new TownListQuery;

        foreach (['active', 'archived', 'trashed'] as $scope) {
            $this->assertSame([], $query->searchableFields($scope));
        }
    }

    public function test_the_search_columns_are_the_name_and_the_reference_code(): void
    {
        $columns = (new \ReflectionClass(TownListQuery::class))->getConstant('SEARCH_COLUMNS');

        $this->assertSame(['LOC_towns.name', 'LOC_towns.ine_code'], $columns);
    }

    public function test_the_query_uses_the_plain_name_lookup_of_the_base(): void
    {
        $method = new \ReflectionMethod(TownListQuery::class, 'firstTrashedByName');

        $this->assertTrue($method->isProtected());
        $this->assertSame('Basics13\Queries\ListQueryBase', $method->getDeclaringClass()->getName());
    }
}
