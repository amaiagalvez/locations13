<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Neighbourhood;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class NeighbourhoodSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Neighbourhood::class) ?? false;
    }
}
