<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class RoleAssignmentService
{
    public function __construct(private AuditLogger $audit) {}

    /** @param string[] $roleNames */
    public function assign(User $actor, User $target, array $roleNames): void
    {
        // RG-AUTH-01 : jamais son propre rôle.
        if ($actor->is($target)) {
            throw new AuthorizationException('Vous ne pouvez pas modifier votre propre rôle.');
        }

        // Moindre privilège : on ne touche qu'aux comptes de niveau inférieur au sien.
        if ($actor->hierarchyLevel() >= $target->hierarchyLevel()) {
            throw new AuthorizationException('Ce compte est de niveau égal ou supérieur au vôtre.');
        }

        $names = array_values(array_unique($roleNames));
        $roles = Role::whereIn('name', $names)->where('guard_name', 'web')->get();

        if ($roles->count() !== count($names)) {
            throw new InvalidArgumentException('Un des rôles demandés n\'existe pas.');
        }

        foreach ($roles as $role) {
            if (! $actor->canManageRole($role)) {
                throw new AuthorizationException("Le rôle « {$role->name} » dépasse votre périmètre.");
            }
        }

        $before = $target->getRoleNames()->all();
        $target->syncRoles($roles);

        $this->audit->log('roles.assigned', 'auth', $target, [
            'before' => $before,
            'after' => $roles->pluck('name')->all(),
        ], $actor);
    }
}
