<?php

namespace Locations13\Tests\Unit\Queries;

use Locations13\Models\Town;
use Locations13\Models\State;
use Locations13\Models\Country;
use PHPUnit\Framework\TestCase;
use Locations13\Models\District;
use Locations13\Models\Community;
use Basics13\Queries\ListQueryBase;
use Locations13\Models\Neighbourhood;
use Locations13\Queries\Towns\TownListQuery;
use Locations13\Queries\States\StateListQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use Locations13\Queries\Countries\CountryListQuery;
use Locations13\Queries\Districts\DistrictListQuery;
use Locations13\Queries\Towns\TownSelectOptionsQuery;
use Locations13\Queries\Communities\CommunityListQuery;
use Locations13\Queries\States\StateSelectOptionsQuery;
use Locations13\Queries\Countries\CountrySelectOptionsQuery;
use Locations13\Queries\Districts\DistrictSelectOptionsQuery;
use Locations13\Queries\Neighbourhoods\NeighbourhoodListQuery;
use Locations13\Queries\Communities\CommunitySelectOptionsQuery;
use Locations13\Queries\Neighbourhoods\NeighbourhoodSelectOptionsQuery;

final class LocationQueryHooksTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: class-string}>
     */
    public static function queries(): array
    {
        return [
            'state list' => [StateListQuery::class, State::class],
            'state options' => [StateSelectOptionsQuery::class, State::class],
            'community list' => [CommunityListQuery::class, Community::class],
            'community options' => [CommunitySelectOptionsQuery::class, Community::class],
            'country list' => [CountryListQuery::class, Country::class],
            'country options' => [CountrySelectOptionsQuery::class, Country::class],
            'district list' => [DistrictListQuery::class, District::class],
            'district options' => [DistrictSelectOptionsQuery::class, District::class],
            'town list' => [TownListQuery::class, Town::class],
            'town options' => [TownSelectOptionsQuery::class, Town::class],
            'neighbourhood list' => [NeighbourhoodListQuery::class, Neighbourhood::class],
            'neighbourhood options' => [NeighbourhoodSelectOptionsQuery::class, Neighbourhood::class],
        ];
    }

    /**
     * @param  class-string  $queryClass
     * @param  class-string  $modelClass
     */
    #[DataProvider('queries')]
    public function test_the_query_names_its_model_and_adds_no_search_columns(string $queryClass, string $modelClass): void
    {
        $query = new $queryClass;
        $this->assertInstanceOf(ListQueryBase::class, $query);

        $this->assertSame($modelClass, (new \ReflectionMethod($query, 'getModelClass'))->invoke($query));
        $this->assertSame(
            ['LOC_x.name'],
            (new \ReflectionMethod($query, 'searchColumns'))->invoke($query, 'active', ['LOC_x.name']),
        );
    }

    /**
     * @param  class-string  $queryClass
     * @param  class-string  $modelClass
     */
    #[DataProvider('queries')]
    public function test_the_query_offers_no_advanced_filters(string $queryClass, string $modelClass): void
    {
        $query = new $queryClass;
        $this->assertInstanceOf(ListQueryBase::class, $query);

        $this->assertSame([], $query->searchableFields('active'));
    }
}
