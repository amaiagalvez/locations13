<?php

namespace Locations13\Policies;

use Locations13\Models\Town;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Town abilities, each one granted by its `towns.{action}` permission.
 */
class TownPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'towns.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'towns.create');
    }

    public function update(User $user, Town $town): bool
    {
        return $this->permits($user, 'towns.edit');
    }

    public function delete(User $user, Town $town): bool
    {
        return $this->permits($user, 'towns.delete');
    }

    public function restore(User $user, Town $town): bool
    {
        return $this->permits($user, 'towns.restore');
    }

    public function forceDelete(User $user, Town $town): bool
    {
        return $this->permits($user, 'towns.force_delete');
    }

    public function archive(User $user, Town $town): bool
    {
        return $this->permits($user, 'towns.archive');
    }

    public function activate(User $user, Town $town): bool
    {
        return $this->permits($user, 'towns.archive');
    }
}
