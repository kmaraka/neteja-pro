<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('inscrit un client, enregistre les consentements et envoie le mail de vérification', function () {
    Notification::fake();

    $this->postJson('/api/auth/register', [
        'name' => 'Awa Koné',
        'email' => 'Awa@Example.com',
        'phone' => '07 00 00 00 00',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'accept_terms' => true,
    ], spa())->assertCreated();

    $user = User::where('email', 'awa@example.com')->firstOrFail();

    expect($user->hasRole('client'))->toBeTrue()
        ->and($user->phone)->toBe('+2250700000000')
        ->and($user->consents)->toHaveCount(2);

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('refuse une inscription sans acceptation des conditions', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Awa',
        'email' => 'awa@example.com',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
    ], spa())->assertUnprocessable()->assertJsonValidationErrors('accept_terms');
});

it('refuse un mot de passe trop faible', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Awa',
        'email' => 'awa@example.com',
        'password' => 'abc',
        'password_confirmation' => 'abc',
        'accept_terms' => true,
    ], spa())->assertUnprocessable()->assertJsonValidationErrors('password');
});
