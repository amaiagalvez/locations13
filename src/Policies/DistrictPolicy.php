<?php

namespace Locations13\Policies;

use Locations13\Models\District;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * District abilities, each one granted by its `districts.{action}` permission.
 */
class DistrictPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'districts.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'districts.create');
    }

    public function update(User $user, District $district): bool
    {
        return $this->permits($user, 'districts.edit');
    }

    public function delete(User $user, District $district): bool
    {
        return $this->permits($user, 'districts.delete');
    }

    public function restore(User $user, District $district): bool
    {
        return $this->permits($user, 'districts.restore');
    }

    public function forceDelete(User $user, District $district): bool
    {
        return $this->permits($user, 'districts.force_delete');
    }

    public function archive(User $user, District $district): bool
    {
        return $this->permits($user, 'districts.archive');
    }

    public function activate(User $user, District $district): bool
    {
        return $this->permits($user, 'districts.archive');
    }
}
