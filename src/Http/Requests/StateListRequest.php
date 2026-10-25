<?php

namespace Locations13\Http\Requests;

use Locations13\Models\State;
use Basics13\Http\Requests\SearchableListRequest;

class StateListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', State::class) ?? false;
    }
}
