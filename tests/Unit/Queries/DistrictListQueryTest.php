<?php

namespace Locations13\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Locations13\Queries\Districts\DistrictListQuery;

class DistrictListQueryTest extends TestCase
{
    public function test_query_can_filter_by_state(): void
    {
        foreach (['active', 'archived', 'trashed'] as $method) {
            $this->assertTrue((new \ReflectionMethod(DistrictListQuery::class, $method))->isPublic());
        }
    }

    public function test_query_can_find_trashed_by_name(): void
    {
        $this->assertTrue((new \ReflectionMethod(DistrictListQuery::class, 'findTrashedByName'))->isPublic());
    }

    public function test_state_counts_returns_array_with_expected_keys(): void
    {
        $query = new DistrictListQuery;

        $this->assertSame(
            ['active' => 10, 'archived' => 5, 'trashed' => 3],
            $query->stateCounts(10, 5, 3),
        );
    }

    public function test_the_list_offers_no_advanced_filters_in_any_state(): void
    {
        $query = new DistrictListQuery;

        foreach (['active', 'archived', 'trashed'] as $scope) {
            $this->assertSame([], $query->searchableFields($scope));
        }
    }

    public function test_the_search_columns_are_the_name_and_the_reference_code(): void
    {
        $columns = (new \ReflectionClass(DistrictListQuery::class))->getConstant('SEARCH_COLUMNS');

        $this->assertSame(['LOC_districts.name', 'LOC_districts.ine_code'], $columns);
    }

    public function test_the_query_uses_the_plain_name_lookup_of_the_base(): void
    {
        $method = new \ReflectionMethod(DistrictListQuery::class, 'firstTrashedByName');

        $this->assertTrue($method->isProtected());
        $this->assertSame('Basics13\Queries\ListQueryBase', $method->getDeclaringClass()->getName());
    }
}
