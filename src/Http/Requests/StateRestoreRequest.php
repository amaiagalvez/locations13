<?php

namespace Locations13\Http\Requests;

use Locations13\Models\State;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<State>
 */
final class StateRestoreRequest extends RestoreRequest
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
