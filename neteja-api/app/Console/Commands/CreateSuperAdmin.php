<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'neteja:super-admin {email} {--name=Super Admin} {--password=}';

    protected $description = 'Crée le Super Administrateur (compte de plus haut niveau)';

    public function handle(): int
    {
        if (! Role::where('name', 'super-admin')->exists()) {
            $this->error('Rôle introuvable. Lancez d\'abord : php artisan db:seed --class=RolesAndPermissionsSeeder');

            return self::FAILURE;
        }

        $email = Str::lower($this->argument('email'));
        $password = $this->option('password') ?: $this->secret('Mot de passe');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'must_change_password' => false,
        ])->save();
        $user->assignRole('super-admin');

        $this->info("Super administrateur créé : {$email}");

        return self::SUCCESS;
    }
}
