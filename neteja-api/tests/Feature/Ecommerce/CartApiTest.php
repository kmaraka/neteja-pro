<?php

use App\Models\User;
use App\Modules\Ecommerce\Models\Cart;
use App\Modules\Ecommerce\Models\DeliveryZone;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;
use App\Modules\Ecommerce\Models\PromoCode;

function cartVariant(int $price = 1500, int $stock = 10): ProductVariant
{
    $product = Product::factory()->create();

    return ProductVariant::factory()->for($product)->create(['price_fcfa' => $price, 'stock' => $stock]);
}

function addToCart($test, ProductVariant $variant, int $quantity = 1, array $headers = [])
{
    return $test->postJson('/api/cart/items', ['variant_id' => $variant->id, 'quantity' => $quantity], $headers);
}

it('un visiteur ajoute un article et reçoit un jeton de panier', function () {
    $response = addToCart($this, cartVariant(1500), 2)->assertCreated();

    expect(strlen($response->json('data.token')))->toBe(40);

    $response->assertJsonCount(1, 'data.lines')
        ->assertJsonPath('data.items_count', 2)
        ->assertJsonPath('data.subtotal_fcfa', 3000);
});

it('relit le panier du visiteur avec son jeton, et renvoie un panier vide sans jeton', function () {
    $token = addToCart($this, cartVariant(), 1)->json('data.token');

    $this->getJson('/api/cart', ['X-Cart-Token' => $token])->assertOk()->assertJsonCount(1, 'data.lines');

    $this->getJson('/api/cart')
        ->assertOk()
        ->assertJsonCount(0, 'data.lines')
        ->assertJsonPath('data.token', null)
        ->assertJsonPath('data.total_fcfa', 0);
});

it("additionne les quantités d'un même article", function () {
    $variant = cartVariant();
    $headers = ['X-Cart-Token' => addToCart($this, $variant, 2)->json('data.token')];

    addToCart($this, $variant, 3, $headers)
        ->assertCreated()
        ->assertJsonCount(1, 'data.lines')
        ->assertJsonPath('data.lines.0.quantity', 5);
});

it('refuse une quantité au-dessus du stock sans révéler un stock confortable', function () {
    $low = cartVariant(1500, 3);
    $big = cartVariant(1500, 20);

    addToCart($this, $low, 5)->assertUnprocessable()
        ->assertJsonPath('errors.quantity.0', "Il n'en reste que 3 en stock.");

    addToCart($this, $big, 50)->assertUnprocessable()
        ->assertJsonPath('errors.quantity.0', 'Stock insuffisant pour cette quantité.');

    expect(Cart::count())->toBe(0); // aucun panier orphelin après un refus
});

it("refuse un article hors ligne, d'une catégorie désactivée ou inconnu", function () {
    $offline = cartVariant();
    $offline->product->update(['is_active' => false]);

    $hiddenCategory = cartVariant();
    $hiddenCategory->product->category->update(['is_active' => false]);

    $inactiveVariant = cartVariant();
    $inactiveVariant->update(['is_active' => false]);

    foreach ([$offline, $hiddenCategory, $inactiveVariant] as $variant) {
        addToCart($this, $variant)->assertUnprocessable()->assertJsonValidationErrors('variant_id');
    }

    $this->postJson('/api/cart/items', ['variant_id' => 999999])->assertUnprocessable();
});

it('modifie puis supprime une ligne', function () {
    $headers = ['X-Cart-Token' => addToCart($this, cartVariant(1000, 10), 2)->json('data.token')];
    $itemId = $this->getJson('/api/cart', $headers)->json('data.lines.0.item_id');

    $this->putJson("/api/cart/items/{$itemId}", ['quantity' => 4], $headers)
        ->assertOk()->assertJsonPath('data.subtotal_fcfa', 4000);

    $this->putJson("/api/cart/items/{$itemId}", ['quantity' => 99], $headers)
        ->assertUnprocessable()->assertJsonValidationErrors('quantity');

    $this->deleteJson("/api/cart/items/{$itemId}", [], $headers)
        ->assertOk()->assertJsonCount(0, 'data.lines');
});

it("ne laisse pas toucher aux lignes d'un autre panier", function () {
    $headersA = ['X-Cart-Token' => addToCart($this, cartVariant(), 1)->json('data.token')];
    $headersB = ['X-Cart-Token' => addToCart($this, cartVariant(), 1)->json('data.token')];
    $itemOfA = $this->getJson('/api/cart', $headersA)->json('data.lines.0.item_id');

    $this->putJson("/api/cart/items/{$itemOfA}", ['quantity' => 2], $headersB)->assertNotFound();
    $this->deleteJson("/api/cart/items/{$itemOfA}", [], $headersB)->assertNotFound();
});

it('un utilisateur connecté a un panier lié à son compte, sans jeton', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');

    addToCart($this, cartVariant(), 1)->assertCreated()->assertJsonPath('data.token', null);
    $this->getJson('/api/cart')->assertJsonCount(1, 'data.lines');

    expect(Cart::where('user_id', $user->id)->count())->toBe(1);
});

it('fusionne le panier du visiteur dans celui du compte', function () {
    $a = cartVariant(1000, 10);
    $b = cartVariant(2000, 10);
    $user = User::factory()->create();

    // Panier du visiteur : 3 × A et 1 × B
    $guestToken = addToCart($this, $a, 3)->json('data.token');
    addToCart($this, $b, 1, ['X-Cart-Token' => $guestToken]);

    // Panier du compte : 2 × A
    $userCart = Cart::factory()->create(['user_id' => $user->id, 'token' => null]);
    $userCart->items()->create(['product_variant_id' => $a->id, 'quantity' => 2]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/cart/merge', [], ['X-Cart-Token' => $guestToken])
        ->assertOk()
        ->assertJsonPath('merge.merged', true)
        ->assertJsonCount(2, 'data.lines')
        ->assertJsonPath('data.items_count', 6); // 5 × A + 1 × B

    expect(Cart::where('token', $guestToken)->exists())->toBeFalse();
});

it('plafonne la fusion au stock disponible', function () {
    $variant = cartVariant(1000, 4);
    $user = User::factory()->create();

    $guestToken = addToCart($this, $variant, 3)->json('data.token');

    $userCart = Cart::factory()->create(['user_id' => $user->id, 'token' => null]);
    $userCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 3]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/cart/merge', [], ['X-Cart-Token' => $guestToken])
        ->assertOk()
        ->assertJsonPath('data.lines.0.quantity', 4)
        ->assertJsonPath('merge.adjusted', 1);
});

it('exige une connexion pour fusionner', function () {
    $this->postJson('/api/cart/merge')->assertUnauthorized();
});

it('applique un code promo et calcule le total avec la livraison', function () {
    DeliveryZone::factory()->create(['commune' => 'Cocody', 'fee_fcfa' => 2000]);
    PromoCode::factory()->create(['code' => 'BIENVENUE10', 'type' => 'percent', 'value' => 10]);

    $headers = ['X-Cart-Token' => addToCart($this, cartVariant(5000, 10), 2)->json('data.token')]; // 10 000

    $this->postJson('/api/cart/promo', ['code' => ' bienvenue10 '], $headers)
        ->assertOk()
        ->assertJsonPath('data.promo.code', 'BIENVENUE10')
        ->assertJsonPath('data.discount_fcfa', 1000)
        ->assertJsonPath('data.total_fcfa', 9000);

    $this->getJson('/api/cart?commune=Cocody', $headers)
        ->assertJsonPath('data.delivery_fee_fcfa', 2000)
        ->assertJsonPath('data.total_fcfa', 11000);
});

it('refuse un code inconnu, expiré ou sur un panier vide', function () {
    PromoCode::factory()->create(['code' => 'VIEUX', 'ends_at' => now()->subDay()]);
    $headers = ['X-Cart-Token' => addToCart($this, cartVariant(), 1)->json('data.token')];

    $this->postJson('/api/cart/promo', ['code' => 'NIMPORTE'], $headers)
        ->assertUnprocessable()->assertJsonPath('errors.code.0', 'Ce code promo est inconnu.');

    $this->postJson('/api/cart/promo', ['code' => 'VIEUX'], $headers)
        ->assertUnprocessable()->assertJsonPath('errors.code.0', 'Ce code promo a expiré.');

    $this->postJson('/api/cart/promo', ['code' => 'VIEUX'])
        ->assertUnprocessable()->assertJsonPath('errors.code.0', 'Votre panier est vide.');
});

it('retire automatiquement un code devenu invalide', function () {
    $promo = PromoCode::factory()->create(['code' => 'ECLAIR']);
    $headers = ['X-Cart-Token' => addToCart($this, cartVariant(1000, 10), 1)->json('data.token')];

    $this->postJson('/api/cart/promo', ['code' => 'ECLAIR'], $headers)->assertOk();

    $promo->update(['ends_at' => now()->subMinute()]);

    $this->getJson('/api/cart', $headers)
        ->assertOk()
        ->assertJsonPath('data.promo', null)
        ->assertJsonPath('data.discount_fcfa', 0)
        ->assertJsonPath('data.promo_error', 'Ce code promo a expiré.');

    expect(Cart::first()->promo_code_id)->toBeNull();
});

it('ne laisse pas une remise fixe dépasser le sous-total', function () {
    PromoCode::factory()->create(['code' => 'GROS', 'type' => 'fixed', 'value' => 50000]);
    $headers = ['X-Cart-Token' => addToCart($this, cartVariant(3000, 10), 1)->json('data.token')];

    $this->postJson('/api/cart/promo', ['code' => 'GROS'], $headers)
        ->assertOk()
        ->assertJsonPath('data.discount_fcfa', 3000)
        ->assertJsonPath('data.total_fcfa', 0);
});

it('signale une commune non desservie', function () {
    $headers = ['X-Cart-Token' => addToCart($this, cartVariant(), 1)->json('data.token')];

    $this->getJson('/api/cart?commune=Paris', $headers)
        ->assertOk()
        ->assertJsonPath('data.delivery', null)
        ->assertJsonPath('data.delivery_error', 'Nous ne livrons pas encore dans cette commune.');
});

it('signale les lignes devenues indisponibles', function () {
    $variant = cartVariant(1000, 10);
    $headers = ['X-Cart-Token' => addToCart($this, $variant, 3)->json('data.token')];

    $variant->update(['stock' => 1]); // le stock a baissé depuis l'ajout

    $this->getJson('/api/cart', $headers)
        ->assertOk()
        ->assertJsonPath('data.has_issues', true)
        ->assertJsonPath('data.lines.0.issue', 'insufficient_stock')
        ->assertJsonPath('data.lines.0.stock_left', 1);

    $variant->product->update(['is_active' => false]);

    $this->getJson('/api/cart', $headers)->assertJsonPath('data.lines.0.issue', 'unavailable');
});

it('vide le panier', function () {
    $headers = ['X-Cart-Token' => addToCart($this, cartVariant(), 2)->json('data.token')];

    $this->deleteJson('/api/cart', [], $headers)
        ->assertOk()
        ->assertJsonCount(0, 'data.lines')
        ->assertJsonPath('data.total_fcfa', 0);
});

it("supprime le panier lors de l'effacement du compte", function () {
    $user = User::factory()->create();
    Cart::factory()->create(['user_id' => $user->id, 'token' => null]);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/account')->assertNoContent();

    expect(Cart::where('user_id', $user->id)->count())->toBe(0);
});
