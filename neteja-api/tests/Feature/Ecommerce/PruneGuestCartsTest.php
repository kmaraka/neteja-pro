<?php

use App\Models\User;
use App\Modules\Ecommerce\Models\Cart;

it('supprime les paniers de visiteurs inactifs mais jamais ceux des comptes', function () {
    $oldGuest = Cart::factory()->create(['last_activity_at' => now()->subDays(40)]);
    $recentGuest = Cart::factory()->create(['last_activity_at' => now()->subDays(2)]);
    $oldUserCart = Cart::factory()->create([
        'user_id' => User::factory()->create()->id,
        'token' => null,
        'last_activity_at' => now()->subDays(90),
    ]);

    $this->artisan('shop:prune-guest-carts')->assertSuccessful();

    expect(Cart::find($oldGuest->id))->toBeNull()
        ->and(Cart::find($recentGuest->id))->not->toBeNull()
        ->and(Cart::find($oldUserCart->id))->not->toBeNull();
});
