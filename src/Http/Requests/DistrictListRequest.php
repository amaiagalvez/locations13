<?php

namespace Locations13\Http\Requests;

use Locations13\Models\District;
use Basics13\Http\Requests\SearchableListRequest;

class DistrictListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', District::class) ?? false;
    }
}
