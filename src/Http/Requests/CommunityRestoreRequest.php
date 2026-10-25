<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Community;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<Community>
 */
final class CommunityRestoreRequest extends RestoreRequest
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
