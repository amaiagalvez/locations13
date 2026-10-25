<?php

namespace Locations13\Policies;

use Locations13\Models\Neighbourhood;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Neighbourhood abilities, each one granted by its `neighbourhoods.{action}` permission.
 */
class NeighbourhoodPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'neighbourhoods.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'neighbourhoods.create');
    }

    public function update(User $user, Neighbourhood $neighbourhood): bool
    {
        return $this->permits($user, 'neighbourhoods.edit');
    }

    public function delete(User $user, Neighbourhood $neighbourhood): bool
    {
        return $this->permits($user, 'neighbourhoods.delete');
    }

    public function restore(User $user, Neighbourhood $neighbourhood): bool
    {
        return $this->permits($user, 'neighbourhoods.restore');
    }

    public function forceDelete(User $user, Neighbourhood $neighbourhood): bool
    {
        return $this->permits($user, 'neighbourhoods.force_delete');
    }

    public function archive(User $user, Neighbourhood $neighbourhood): bool
    {
        return $this->permits($user, 'neighbourhoods.archive');
    }

    public function activate(User $user, Neighbourhood $neighbourhood): bool
    {
        return $this->permits($user, 'neighbourhoods.archive');
    }
}
