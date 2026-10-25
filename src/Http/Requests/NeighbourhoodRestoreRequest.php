<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Neighbourhood;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<Neighbourhood>
 */
final class NeighbourhoodRestoreRequest extends RestoreRequest
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
