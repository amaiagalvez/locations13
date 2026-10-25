<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Country;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class CountrySelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Country::class) ?? false;
    }
}
