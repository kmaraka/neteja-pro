<?php

namespace App\Modules\Compliance\Services;

use App\Models\User;
use App\Modules\Auth\Services\DeviceSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountErasureService
{
    public function __construct(private DeviceSessionService $devices) {}

    /** Droit à l'oubli : on anonymise, on ne supprime pas (les factures gardent leur lien). */
    public function anonymize(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->devices->revokeOthers($user); // jetons d'API et sessions de tous les appareils
            $user->syncRoles([]);
            $user->addresses()->delete();        // adresses de livraison : données personnelles
            $user->cart()->delete();             // panier du compte

            $user->forceFill([
                'name' => 'Utilisateur supprimé',
                'email' => "supprime-{$user->id}@anonyme.invalid",
                'phone' => null,
                'prefers_sms' => false,
                'email_verified_at' => null,
                'password' => Str::random(40),
                'remember_token' => null,
                'app_authentication_secret' => null,          // 2FA
                'app_authentication_recovery_codes' => null,
                'anonymized_at' => now(),
            ])->save();
        });
    }
}
