<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Town;
use Basics13\Http\Requests\SearchableListRequest;

class TownListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Town::class) ?? false;
    }
}
