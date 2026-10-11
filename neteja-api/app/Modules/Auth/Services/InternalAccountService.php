<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Core\Sms\SmsGateway;
use App\Modules\Core\Support\Phone;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class InternalAccountService
{
    public function __construct(
        private RoleAssignmentService $roles,
        private DeviceSessionService $devices,
        private AuditLogger $audit,
        private SmsGateway $sms,
    ) {}

    /**
     * @param  array{name: string, phone: string, email?: ?string}  $data
     * @param  string[]  $roleNames
     * @return array{0: User, 1: bool} le compte créé et l'état d'envoi du SMS
     */
    public function create(User $actor, array $data, array $roleNames): array
    {
        if (! $actor->can('users.create')) {
            throw new AuthorizationException('Vous ne pouvez pas créer de comptes.');
        }

        if ($roleNames === []) {
            throw new InvalidArgumentException('Au moins un rôle est requis.');
        }

        $password = $this->generatePassword();

        // Transaction : si l'attribution du rôle est refusée, le compte n'est pas créé.
        $user = DB::transaction(function () use ($actor, $data, $roleNames, $password) {
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => Phone::normalize($data['phone']),
                'password' => $password,
                'must_change_password' => true,
                // Un administrateur qui saisit l'email se porte garant de l'adresse.
                'email_verified_at' => filled($data['email'] ?? null) ? now() : null,
            ])->save();

            $this->roles->assign($actor, $user, $roleNames);

            return $user;
        });

        $this->audit->log('users.created_internal', 'auth', $user, ['roles' => $roleNames], $actor);

        return [$user, $this->sendTemporaryPassword($user, $password)];
    }

    /** Génère un nouveau mot de passe temporaire et l'envoie par SMS. Retourne l'état d'envoi. */
    public function resetPassword(User $actor, User $target): bool
    {
        if ($actor->is($target) || $actor->hierarchyLevel() >= $target->hierarchyLevel()) {
            throw new AuthorizationException('Ce compte est de niveau égal ou supérieur au vôtre.');
        }

        if (! $target->phone) {
            throw new InvalidArgumentException("Ce compte n'a pas de numéro de téléphone.");
        }

        $password = $this->generatePassword();

        $target->forceFill(['password' => $password, 'must_change_password' => true])->save();
        $this->devices->revokeOthers($target); // toutes les sessions du compte sont fermées
        $this->audit->log('users.password_reset_by_admin', 'auth', $target, user: $actor);

        return $this->sendTemporaryPassword($target, $password);
    }

    private function sendTemporaryPassword(User $user, string $password): bool
    {
        try {
            // Sans accents : un accent peut faire passer le SMS en UCS-2 (limite de 70 caractères).
            $this->sms->send(
                $user->phone,
                "Neteja : votre mot de passe temporaire est {$password} . Changez-le des votre premiere connexion.",
            );

            return true;
        } catch (Throwable $e) {
            report($e); // le compte existe : l'admin pourra relancer l'envoi

            return false;
        }
    }

    /** 11 caractères lisibles (sans 0/O, 1/l/I) : 3 majuscules, 5 minuscules, 3 chiffres. */
    private function generatePassword(): string
    {
        $pick = function (string $set, int $length): string {
            $out = '';
            for ($i = 0; $i < $length; $i++) {
                $out .= $set[random_int(0, strlen($set) - 1)];
            }

            return $out;
        };

        return $pick('ABCDEFGHJKLMNPQRSTUVWXYZ', 3)
            .$pick('abcdefghijkmnopqrstuvwxyz', 5)
            .$pick('23456789', 3);
    }
}
