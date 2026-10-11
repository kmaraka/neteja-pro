<?php

use App\Models\User;
use App\Modules\Auth\Services\DeviceSessionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('bloque les routes métier tant que le mot de passe temporaire est actif', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user, 'sanctum')->getJson('/api/audit-logs')
        ->assertForbidden()
        ->assertJson(['code' => 'password_change_required']);
});

it('permet de changer le mot de passe temporaire', function () {
    $user = User::factory()->create(['password' => 'Temp12345', 'must_change_password' => true]);

    $this->actingAs($user, 'sanctum')->postJson('/api/auth/password/change', [
        'current_password' => 'Temp12345',
        'password' => 'Nouveau123',
        'password_confirmation' => 'Nouveau123',
    ])->assertNoContent();

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('Nouveau123', $user->password))->toBeTrue();
});

it('refuse le changement si le mot de passe actuel est faux', function () {
    $user = User::factory()->create(['password' => 'Temp12345']);

    $this->actingAs($user, 'sanctum')->postJson('/api/auth/password/change', [
        'current_password' => 'Faux12345',
        'password' => 'Nouveau123',
        'password_confirmation' => 'Nouveau123',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
});

it('réinitialise le mot de passe via le lien reçu par email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'a@b.com']);

    $this->postJson('/api/auth/password/forgot', ['email' => 'a@b.com'])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->postJson('/api/auth/password/reset', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'Nouveau123',
            'password_confirmation' => 'Nouveau123',
        ])->assertOk();

        return true;
    });

    expect(Hash::check('Nouveau123', $user->fresh()->password))->toBeTrue();
});

it('révoque les jetons lors de la déconnexion des autres appareils', function () {
    $user = User::factory()->create();
    $user->createToken('telephone');
    expect($user->tokens()->count())->toBe(1);

    app(DeviceSessionService::class)->revokeOthers($user);

    expect($user->tokens()->count())->toBe(0);
});
