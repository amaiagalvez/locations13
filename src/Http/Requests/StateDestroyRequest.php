<?php

namespace Locations13\Http\Requests;

use Locations13\Models\State;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<State>
 */
final class StateDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return State
     */
    protected function findTrashed(): Model
    {
        return State::onlyTrashed()
            ->findOrFail((int) $this->route('state'));
    }
}
