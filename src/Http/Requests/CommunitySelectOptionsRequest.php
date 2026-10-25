<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Community;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class CommunitySelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Community::class) ?? false;
    }
}
