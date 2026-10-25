<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Country;
use Basics13\Http\Requests\SearchableListRequest;

class CountryListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Country::class) ?? false;
    }
}
