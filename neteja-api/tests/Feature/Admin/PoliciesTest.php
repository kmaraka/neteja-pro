<?php

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Auth\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('permet à un admin de modifier un client mais pas un super-admin', function () {
    $admin = userWithRole('admin');

    expect($admin->can('update', userWithRole('client')))->toBeTrue()
        ->and($admin->can('update', userWithRole('super-admin')))->toBeFalse();
});

it("interdit à un admin d'anonymiser son propre compte", function () {
    $admin = userWithRole('admin');

    expect($admin->can('delete', $admin))->toBeFalse();
});

it('réserve la gestion des rôles au super-admin', function () {
    expect(userWithRole('admin')->can('create', Role::class))->toBeFalse()
        ->and(userWithRole('admin')->can('viewAny', Role::class))->toBeTrue()
        ->and(userWithRole('super-admin')->can('create', Role::class))->toBeTrue();
});

it('empêche de supprimer un rôle système, même pour le super-admin', function () {
    $role = Role::findByName('admin');

    expect($role->delete())->toBeFalse()
        ->and(Role::where('name', 'admin')->exists())->toBeTrue();
});

it('masque le journal aux clients et le montre aux admins', function () {
    expect(userWithRole('client')->can('viewAny', AuditLog::class))->toBeFalse()
        ->and(userWithRole('admin')->can('viewAny', AuditLog::class))->toBeTrue();
});
