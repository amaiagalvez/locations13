<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<Country>
 */
final class CountryDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return Country
     */
    protected function findTrashed(): Model
    {
        return Country::onlyTrashed()
            ->findOrFail((int) $this->route('country'));
    }
}
