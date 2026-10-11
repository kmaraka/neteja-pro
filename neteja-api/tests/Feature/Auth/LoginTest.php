<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it("refuse la connexion tant que l'email d'un client n'est pas vérifié", function () {
    $user = User::factory()->unverified()->create(['email' => 'a@b.com', 'password' => 'Secret123']);
    $user->assignRole('client');

    $this->postJson('/api/auth/login', ['login' => 'a@b.com', 'password' => 'Secret123'], spa())
        ->assertForbidden()
        ->assertJson(['code' => 'email_not_verified']);

    $this->assertGuest('web');
});

it('connecte un client vérifié par email', function () {
    $user = User::factory()->create(['email' => 'a@b.com', 'password' => 'Secret123']);
    $user->assignRole('client');

    $this->postJson('/api/auth/login', ['login' => 'a@b.com', 'password' => 'Secret123'], spa())
        ->assertOk()
        ->assertJsonPath('user.email', 'a@b.com');
});

it('connecte un intervenant par numéro de téléphone', function () {
    $user = User::factory()->create([
        'email' => null,
        'phone' => '+2250700000001',
        'password' => 'Temp12345',
        'must_change_password' => true,
    ]);
    $user->assignRole('intervenant');

    $this->postJson('/api/auth/login', ['login' => '07 00 00 00 01', 'password' => 'Temp12345'], spa())
        ->assertOk()
        ->assertJsonPath('user.must_change_password', true);
});

it('refuse des identifiants incorrects', function () {
    User::factory()->create(['email' => 'a@b.com', 'password' => 'Secret123']);

    $this->postJson('/api/auth/login', ['login' => 'a@b.com', 'password' => 'Mauvais123'], spa())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('login');
});

it('bloque la 6e tentative de connexion en une minute', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/auth/login', ['login' => 'x@y.com', 'password' => 'Mauvais123'], spa());
    }

    $this->postJson('/api/auth/login', ['login' => 'x@y.com', 'password' => 'Mauvais123'], spa())
        ->assertTooManyRequests();
});
