<?php

namespace Locations13\Policies;

use Locations13\Models\Country;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Country abilities, each one granted by its `countries.{action}` permission.
 */
class CountryPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'countries.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->permits($user, 'countries.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Country $country): bool
    {
        return $this->permits($user, 'countries.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Country $country): bool
    {
        return $this->permits($user, 'countries.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Country $country): bool
    {
        return $this->permits($user, 'countries.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Country $country): bool
    {
        return $this->permits($user, 'countries.force_delete');
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Country $country): bool
    {
        return $this->permits($user, 'countries.archive');
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, Country $country): bool
    {
        return $this->permits($user, 'countries.archive');
    }
}
