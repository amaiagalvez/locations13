<?php

namespace Locations13\Tests\Unit;

use Locations13\Models\Town;
use Locations13\Models\State;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Locations13\Models\Community;
use Locations13\Models\Neighbourhood;
use Basics13\Tests\ChecksResourceArchitecture;

class ArchitectureTest extends TestCase
{
    use ChecksResourceArchitecture;

    protected function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    protected function resources(): array
    {
        return [
            'State' => State::class,
            'Community' => Community::class,
            'Country' => Country::class,
            'District' => District::class,
            'Town' => Town::class,
            'Neighbourhood' => Neighbourhood::class,
        ];
    }
}
