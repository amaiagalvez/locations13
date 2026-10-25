<?php

namespace Locations13\Http\Requests;

use Locations13\Models\District;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<District>
 */
final class DistrictDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return District
     */
    protected function findTrashed(): Model
    {
        return District::onlyTrashed()
            ->findOrFail((int) $this->route('district'));
    }
}
