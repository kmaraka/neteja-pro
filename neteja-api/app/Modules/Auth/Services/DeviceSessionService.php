<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeviceSessionService
{
    /** Déconnecte tous les appareils du compte, sauf la session indiquée (si fournie). */
    public function revokeOthers(User $user, ?string $keepSessionId = null): void
    {
        // Jetons d'API éventuels (Sanctum en mode jeton).
        $user->tokens()->delete();

        // Sessions par cookie : elles vivent dans la table "sessions" (pilote "database").
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($keepSessionId, fn ($query) => $query->where('id', '!=', $keepSessionId))
            ->delete();
    }
}
