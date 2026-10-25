<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Town;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<Town>
 */
final class TownRestoreRequest extends RestoreRequest
{
    /**
     * @return Town
     */
    protected function findTrashed(): Model
    {
        return Town::onlyTrashed()
            ->findOrFail((int) $this->route('town'));
    }
}
