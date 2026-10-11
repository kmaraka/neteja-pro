<?php

namespace App\Modules\Auth\Policies;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;

class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('audit.view');
    }

    public function view(User $actor, AuditLog $log): bool
    {
        return $actor->can('audit.view');
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $actor, AuditLog $log): bool
    {
        return false;
    }
}
