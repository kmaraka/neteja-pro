<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Auth\Models\Role;
use App\Modules\Compliance\Models\UserConsent;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'prefers_sms'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function hierarchyLevel(): int
    {
        // Le niveau d'un utilisateur est celui de son rôle le plus puissant (le plus petit nombre).
        return $this->roles->min('hierarchy_level') ?? PHP_INT_MAX;
    }

    /** Rôles autorisés à entrer dans le back-office Filament. */
    public const PANEL_ROLES = ['super-admin', 'admin', 'superviseur'];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->anonymized_at === null
            && $this->hasAnyRole(self::PANEL_ROLES);
    }

    public function canManageRole(Role $role): bool
    {
        return $this->hierarchyLevel() < $role->hierarchy_level;
    }
}
