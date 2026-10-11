<?php

namespace App\Modules\Compliance\Services;

use App\Models\User;

class ConsentService
{
    /** Types de consentement pas encore acceptés dans leur version en vigueur. */
    public function pending(User $user): array
    {
        $pending = [];

        foreach (config('legal.versions') as $type => $version) {
            $accepted = $user->consents()
                ->where('type', $type)
                ->where('version', $version)
                ->exists();

            if (! $accepted) {
                $pending[] = $type;
            }
        }

        return $pending;
    }

    public function accept(User $user, array $types, ?string $ip): void
    {
        foreach ($types as $type) {
            $user->consents()->firstOrCreate(
                ['type' => $type, 'version' => config("legal.versions.$type")],
                ['accepted_at' => now(), 'ip_address' => $ip],
            );
        }
    }
}
