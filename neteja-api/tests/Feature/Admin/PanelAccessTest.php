<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

/** Compte avec rôle et 2FA activé : seul canAccessPanel() décide alors de l'accès. */
function staffWithMfa(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes + ['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $user->assignRole($role);

    return $user;
}

it('redirige un visiteur non connecté vers la connexion', function () {
    $this->get('/admin')->assertRedirect();
});

it('laisse entrer un administrateur qui a activé le 2FA', function () {
    $this->actingAs(staffWithMfa('admin'))->get('/admin')->assertOk();
});

it('laisse entrer le super-admin qui a activé le 2FA', function () {
    $this->actingAs(staffWithMfa('super-admin'))->get('/admin')->assertOk();
});

it('oblige un administrateur sans 2FA à le configurer', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('multi-factor-authentication');
});

it('refuse un client', function () {
    $this->actingAs(staffWithMfa('client'))->get('/admin')->assertForbidden();
});

it('refuse un intervenant', function () {
    $user = User::factory()->create(['email' => null, 'phone' => '+2250700000010']);
    $user->assignRole('intervenant');

    expect($user->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
});

it('refuse un compte anonymisé même avec un rôle admin', function () {
    $user = staffWithMfa('admin', ['anonymized_at' => now()]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
