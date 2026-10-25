<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Neighbourhood;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<Neighbourhood>
 */
final class NeighbourhoodDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return Neighbourhood
     */
    protected function findTrashed(): Model
    {
        return Neighbourhood::onlyTrashed()
            ->findOrFail((int) $this->route('neighbourhood'));
    }
}
