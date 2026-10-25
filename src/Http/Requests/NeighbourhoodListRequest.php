<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Neighbourhood;
use Basics13\Http\Requests\SearchableListRequest;

class NeighbourhoodListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Neighbourhood::class) ?? false;
    }
}
