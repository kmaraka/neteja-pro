<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Auth\Services\TwoFactorService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetTwoFactor extends Command
{
    protected $signature = 'neteja:reset-2fa {email}';

    protected $description = "Réinitialise le 2FA d'un compte (perte du téléphone et des codes de secours)";

    public function handle(TwoFactorService $twoFactor): int
    {
        $user = User::where('email', Str::lower($this->argument('email')))->first();

        if (! $user) {
            $this->error('Compte introuvable.');

            return self::FAILURE;
        }

        $twoFactor->reset($user);

        $this->info("2FA réinitialisé pour {$user->email}. Il sera à reconfigurer à la prochaine connexion.");

        return self::SUCCESS;
    }
}
