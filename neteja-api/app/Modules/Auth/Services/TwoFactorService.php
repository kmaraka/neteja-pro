<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class TwoFactorService
{
    public function __construct(
        private DeviceSessionService $devices,
        private AuditLogger $audit,
    ) {}

    public function isEnabled(User $user): bool
    {
        return filled($user->getAppAuthenticationSecret());
    }

    /**
     * Supprime le 2FA d'un compte (téléphone perdu) : il devra être reconfiguré à la prochaine connexion.
     * Sans $actor : appel depuis la console (accès au serveur = confiance).
     */
    public function reset(User $target, ?User $actor = null): void
    {
        if ($actor !== null) {
            if (! $actor->can('users.update')) {
                throw new AuthorizationException('Vous ne pouvez pas modifier les comptes.');
            }

            if ($actor->is($target) || $actor->hierarchyLevel() >= $target->hierarchyLevel()) {
                throw new AuthorizationException('Ce compte est de niveau égal ou supérieur au vôtre.');
            }
        }

        $target->forceFill([
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->save();

        $this->devices->revokeOthers($target); // toutes les sessions du compte sont fermées

        $this->audit->log('auth.mfa_reset', 'auth', $target, [
            'via' => $actor ? 'admin' : 'console',
        ], $actor);
    }
}
