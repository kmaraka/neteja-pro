<?php

namespace App\Modules\Compliance\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountErasureService
{
    /** Droit à l'oubli : on anonymise, on ne supprime pas (les factures gardent leur lien). */
    public function anonymize(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->syncRoles([]);

            $user->forceFill([
                'name' => 'Utilisateur supprimé',
                'email' => "supprime-{$user->id}@anonyme.invalid",
                'phone' => null,
                'prefers_sms' => false,
                'email_verified_at' => null,
                'password' => Str::random(40),
                'remember_token' => null,
                'anonymized_at' => now(),
            ])->save();
        });
    }
}
