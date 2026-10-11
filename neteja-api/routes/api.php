<?php

use App\Modules\Compliance\Http\Controllers\AccountController;
use App\Modules\Compliance\Http\Controllers\ConsentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Accessibles même sans consentement à jour : sinon on ne pourrait jamais accepter,
    // et un utilisateur doit toujours pouvoir exercer son droit à l'oubli.
    Route::get('/consents/pending', [ConsentController::class, 'pending']);
    Route::post('/consents', [ConsentController::class, 'store']);
    Route::delete('/account', [AccountController::class, 'destroy']);

    // Toutes les futures routes métier iront ici.
    Route::middleware('consent')->group(function () {
        Route::get('/user', fn (Request $request) => $request->user());
    });
});
