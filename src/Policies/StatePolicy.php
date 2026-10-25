<?php

namespace Locations13\Policies;

use Locations13\Models\State;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * State abilities, each one granted by its `states.{action}` permission.
 */
class StatePolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'states.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->permits($user, 'states.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, State $state): bool
    {
        return $this->permits($user, 'states.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, State $state): bool
    {
        return $this->permits($user, 'states.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, State $state): bool
    {
        return $this->permits($user, 'states.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, State $state): bool
    {
        return $this->permits($user, 'states.force_delete');
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, State $state): bool
    {
        return $this->permits($user, 'states.archive');
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, State $state): bool
    {
        return $this->permits($user, 'states.archive');
    }
}
