<?php

use App\Modules\Auth\Http\Controllers\AuditLogController;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\EmailVerificationController;
use App\Modules\Auth\Http\Controllers\PasswordController;
use App\Modules\Compliance\Http\Controllers\AccountController;
use App\Modules\Compliance\Http\Controllers\ConsentController;
use App\Modules\Ecommerce\Http\Controllers\AddressController;
use App\Modules\Ecommerce\Http\Controllers\CartController;
use App\Modules\Ecommerce\Http\Controllers\CatalogController;
use App\Modules\Ecommerce\Http\Controllers\DeliveryZoneController;
use Illuminate\Support\Facades\Route;

// --- Public : authentification ---
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:verification');
    Route::post('password/forgot', [PasswordController::class, 'forgot'])->middleware('throttle:password-reset');
    Route::post('password/reset', [PasswordController::class, 'reset'])->middleware('throttle:password-reset');
});

// --- Public : boutique (consultation libre, sans compte) ---
Route::get('categories', [CatalogController::class, 'categories']);
Route::get('products', [CatalogController::class, 'products']);
Route::get('products/{slug}', [CatalogController::class, 'product']);
Route::get('delivery-zones', [DeliveryZoneController::class, 'index']);

// Panier : ouvert aux visiteurs (jeton X-Cart-Token) comme aux comptes connectés.
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::delete('/', [CartController::class, 'clear']);
    Route::post('items', [CartController::class, 'addItem']);
    Route::put('items/{item}', [CartController::class, 'updateItem'])->whereNumber('item');
    Route::delete('items/{item}', [CartController::class, 'removeItem'])->whereNumber('item');
    Route::post('promo', [CartController::class, 'applyPromo'])->middleware('throttle:promo');
    Route::delete('promo', [CartController::class, 'removePromo']);
    Route::post('merge', [CartController::class, 'merge'])->middleware('auth:sanctum');
});

// --- Connecté ---
Route::middleware('auth:sanctum')->group(function () {
    // Toujours accessibles, même avec un mot de passe temporaire ou des CGU à accepter.
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('auth/password/change', [PasswordController::class, 'change']);
    Route::get('consents/pending', [ConsentController::class, 'pending']);
    Route::post('consents', [ConsentController::class, 'store']);
    Route::delete('account', [AccountController::class, 'destroy']);

    // Routes métier : mot de passe changé PUIS CGU acceptées (l'ordre compte).
    Route::middleware(['password.changed', 'consent'])->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view');

        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::put('addresses/{address}', [AddressController::class, 'update'])->whereNumber('address');
        Route::post('addresses/{address}/default', [AddressController::class, 'makeDefault'])->whereNumber('address');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->whereNumber('address');
    });
});
