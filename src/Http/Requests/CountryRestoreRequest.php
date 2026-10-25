<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<Country>
 */
final class CountryRestoreRequest extends RestoreRequest
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
