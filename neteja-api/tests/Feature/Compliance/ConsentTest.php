<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Route de test, protégée par le middleware de consentement.
    Route::middleware(['auth:sanctum', 'consent'])->get('/_ping', fn () => response()->json(['ok' => true]));
});

it("bloque un utilisateur qui n'a pas accepté les CGU", function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->getJson('/_ping')
        ->assertForbidden()
        ->assertJson(['code' => 'consent_required']);
});

it("laisse passer l'utilisateur après acceptation", function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/consents', ['types' => ['cgu', 'politique_confidentialite']])
        ->assertOk()
        ->assertJson(['pending' => []]);

    $this->actingAs($user, 'sanctum')->getJson('/_ping')->assertOk();
});

it("anonymise le compte sans le supprimer (droit à l'oubli)", function () {
    $user = User::factory()->create(['phone' => '+2250700000000']);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/account')->assertNoContent();

    $user->refresh();
    expect($user->name)->toBe('Utilisateur supprimé')
        ->and($user->phone)->toBeNull()
        ->and($user->email)->toEndWith('@anonyme.invalid');
});
