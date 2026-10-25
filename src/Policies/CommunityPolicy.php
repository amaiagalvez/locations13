<?php

namespace Locations13\Policies;

use Locations13\Models\Community;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Community abilities, each one granted by its `communities.{action}` permission.
 */
class CommunityPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'communities.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->permits($user, 'communities.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Community $community): bool
    {
        return $this->permits($user, 'communities.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Community $community): bool
    {
        return $this->permits($user, 'communities.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Community $community): bool
    {
        return $this->permits($user, 'communities.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Community $community): bool
    {
        return $this->permits($user, 'communities.force_delete');
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Community $community): bool
    {
        return $this->permits($user, 'communities.archive');
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, Community $community): bool
    {
        return $this->permits($user, 'communities.archive');
    }
}
