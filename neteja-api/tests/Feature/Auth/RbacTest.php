<?php

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Auth\Services\RoleAssignmentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it("refuse l'accès à une ressource protégée sans la permission", function () {
    $user = consenting(User::factory()->create());

    $this->actingAs($user, 'sanctum')->getJson('/api/audit-logs')->assertForbidden();
});

it("autorise l'accès avec la permission", function () {
    $user = consenting(User::factory()->create());
    $user->givePermissionTo('audit.view');

    $this->actingAs($user, 'sanctum')->getJson('/api/audit-logs')->assertOk();
});

it('donne tous les droits au super-admin sans permissions listées', function () {
    $user = consenting(User::factory()->create());
    $user->assignRole('super-admin');

    $this->actingAs($user, 'sanctum')->getJson('/api/audit-logs')->assertOk();
});

it('empêche de modifier son propre rôle (RG-AUTH-01)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(fn () => app(RoleAssignmentService::class)->assign($admin, $admin, ['super-admin']))
        ->toThrow(AuthorizationException::class);
});

it("interdit à un admin d'attribuer le rôle super-admin", function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $cible = User::factory()->create();
    $cible->assignRole('client');

    expect(fn () => app(RoleAssignmentService::class)->assign($admin, $cible, ['super-admin']))
        ->toThrow(AuthorizationException::class);
});

it('interdit à un admin de modifier un super-admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $boss = User::factory()->create();
    $boss->assignRole('super-admin');

    expect(fn () => app(RoleAssignmentService::class)->assign($admin, $boss, ['client']))
        ->toThrow(AuthorizationException::class);
});

it("permet à un admin d'attribuer un rôle inférieur et journalise l'action", function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $cible = User::factory()->create();
    $cible->assignRole('client');

    app(RoleAssignmentService::class)->assign($admin, $cible, ['superviseur']);

    expect($cible->fresh()->hasRole('superviseur'))->toBeTrue()
        ->and(AuditLog::where('action', 'roles.assigned')->count())->toBe(1);
});

it('rend le journal d\'audit immuable', function () {
    $log = app(AuditLogger::class)->log('test.action', 'test');

    expect($log->update(['action' => 'modifie']))->toBeFalse()
        ->and($log->delete())->toBeFalse();
});
