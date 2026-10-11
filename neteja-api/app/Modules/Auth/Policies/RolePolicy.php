<?php

namespace App\Modules\Auth\Policies;

use App\Models\User;
use App\Modules\Auth\Models\Role;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('roles.view');
    }

    public function view(User $actor, Role $role): bool
    {
        return $actor->can('roles.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('roles.manage');
    }

    public function update(User $actor, Role $role): bool
    {
        return $actor->can('roles.manage');
    }

    public function delete(User $actor, Role $role): bool
    {
        return $actor->can('roles.manage') && ! $role->is_system;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
