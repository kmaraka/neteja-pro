<?php

use App\Models\User;
use App\Modules\Auth\Services\InternalAccountService;
use App\Modules\Core\Sms\SmsGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Faux fournisseur SMS : il garde les messages en mémoire pour qu'on les vérifie.
    $this->sms = new class implements SmsGateway
    {
        public array $sent = [];

        public function send(string $to, string $message): void
        {
            $this->sent[] = [$to, $message];
        }
    };
    app()->instance(SmsGateway::class, $this->sms);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('crée un intervenant avec un mot de passe temporaire envoyé par SMS', function () {
    [$user, $smsSent] = app(InternalAccountService::class)->create(
        $this->admin,
        ['name' => 'Moussa Traoré', 'phone' => '07 00 00 00 02'],
        ['intervenant'],
    );

    expect($smsSent)->toBeTrue()
        ->and($user->phone)->toBe('+2250700000002')
        ->and($user->email)->toBeNull()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->hasRole('intervenant'))->toBeTrue()
        ->and($this->sms->sent)->toHaveCount(1)
        ->and($this->sms->sent[0][0])->toBe('+2250700000002');

    // Le mot de passe reçu par SMS est bien celui du compte.
    preg_match('/est ([A-Za-z0-9]+)/', $this->sms->sent[0][1], $match);
    expect(Hash::check($match[1], $user->password))->toBeTrue();
});

it("refuse qu'un admin crée un super-admin et n'enregistre aucun compte", function () {
    expect(fn () => app(InternalAccountService::class)->create(
        $this->admin,
        ['name' => 'Intrus', 'phone' => '07 00 00 00 03'],
        ['super-admin'],
    ))->toThrow(AuthorizationException::class);

    expect(User::where('phone', '+2250700000003')->exists())->toBeFalse()
        ->and($this->sms->sent)->toBeEmpty();
});

it("refuse qu'un client crée des comptes", function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    expect(fn () => app(InternalAccountService::class)->create(
        $client, ['name' => 'X', 'phone' => '07 00 00 00 04'], ['client'],
    ))->toThrow(AuthorizationException::class);
});

it('réinitialise le mot de passe et le renvoie par SMS', function () {
    $cible = User::factory()->create(['phone' => '+2250700000005', 'password' => 'Ancien1234']);
    $cible->assignRole('intervenant');

    $smsSent = app(InternalAccountService::class)->resetPassword($this->admin, $cible);

    $cible->refresh();
    expect($smsSent)->toBeTrue()
        ->and($cible->must_change_password)->toBeTrue()
        ->and(Hash::check('Ancien1234', $cible->password))->toBeFalse()
        ->and($this->sms->sent)->toHaveCount(1);
});

it('interdit de réinitialiser le mot de passe d\'un compte de niveau supérieur', function () {
    $boss = User::factory()->create(['phone' => '+2250700000006']);
    $boss->assignRole('super-admin');

    expect(fn () => app(InternalAccountService::class)->resetPassword($this->admin, $boss))
        ->toThrow(AuthorizationException::class);
});
