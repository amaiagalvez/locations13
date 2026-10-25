<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Community;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<Community>
 */
final class CommunityDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return Community
     */
    protected function findTrashed(): Model
    {
        return Community::onlyTrashed()
            ->findOrFail((int) $this->route('community'));
    }
}
