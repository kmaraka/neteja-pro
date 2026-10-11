<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('redirige un visiteur non connecté vers la connexion', function () {
    $this->get('/admin')->assertRedirect();
});

it('laisse entrer un administrateur', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('laisse entrer le super-admin', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('refuse un client', function () {
    $user = User::factory()->create();
    $user->assignRole('client');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('refuse un intervenant', function () {
    $user = User::factory()->create(['email' => null, 'phone' => '+2250700000010']);
    $user->assignRole('intervenant');

    expect($user->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
});

it('refuse un compte anonymisé même avec un rôle admin', function () {
    $user = User::factory()->create(['anonymized_at' => now()]);
    $user->assignRole('admin');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
