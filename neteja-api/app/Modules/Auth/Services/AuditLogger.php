<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function log(
        string $action,
        string $module,
        ?Model $subject = null,
        array $details = [],
        ?User $user = null,
    ): AuditLog {
        $user ??= auth()->user();

        return AuditLog::create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'details' => $details ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
