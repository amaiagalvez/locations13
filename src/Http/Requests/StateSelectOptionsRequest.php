<?php

namespace Locations13\Http\Requests;

use Locations13\Models\State;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class StateSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', State::class) ?? false;
    }
}
