<?php

namespace App\Modules\Auth\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    /** On modifie son propre profil, ou un compte de niveau inférieur au sien. */
    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.update')
            && ($actor->is($target) || $actor->hierarchyLevel() < $target->hierarchyLevel());
    }

    /** "delete" signifie ici anonymiser : jamais soi-même, jamais un niveau égal ou supérieur. */
    public function delete(User $actor, User $target): bool
    {
        return $actor->can('users.delete')
            && ! $actor->is($target)
            && $actor->hierarchyLevel() < $target->hierarchyLevel();
    }

    public function deleteAny(User $actor): bool
    {
        return false; // pas de suppression en masse
    }
}
