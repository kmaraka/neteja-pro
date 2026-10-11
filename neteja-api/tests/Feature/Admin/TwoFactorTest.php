<?php

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Auth\Services\TwoFactorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function mfaUser(string $role): User
{
    $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $user->assignRole($role);

    return $user;
}

it('réinitialise le 2FA d\'un compte de niveau inférieur et journalise', function () {
    $cible = mfaUser('superviseur');

    app(TwoFactorService::class)->reset($cible, $this->admin);

    expect(app(TwoFactorService::class)->isEnabled($cible->fresh()))->toBeFalse()
        ->and(AuditLog::where('action', 'auth.mfa_reset')->where('subject_id', $cible->id)->count())->toBe(1);
});

it('interdit à un admin de réinitialiser le 2FA d\'un super-admin', function () {
    $boss = mfaUser('super-admin');

    expect(fn () => app(TwoFactorService::class)->reset($boss, $this->admin))
        ->toThrow(AuthorizationException::class);

    expect(app(TwoFactorService::class)->isEnabled($boss->fresh()))->toBeTrue();
});

it('interdit à un admin de réinitialiser son propre 2FA', function () {
    $this->admin->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

    expect(fn () => app(TwoFactorService::class)->reset($this->admin, $this->admin))
        ->toThrow(AuthorizationException::class);
});

it('réinitialise le 2FA depuis la console', function () {
    $user = mfaUser('super-admin');
    $user->forceFill(['email' => 'boss@example.com'])->save();

    $this->artisan('neteja:reset-2fa', ['email' => 'boss@example.com'])->assertSuccessful();

    expect(app(TwoFactorService::class)->isEnabled($user->fresh()))->toBeFalse();
});

it('signale un compte introuvable', function () {
    $this->artisan('neteja:reset-2fa', ['email' => 'inconnu@example.com'])->assertFailed();
});
