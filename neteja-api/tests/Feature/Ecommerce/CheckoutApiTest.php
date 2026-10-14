<?php

use App\Models\User;
use App\Modules\Ecommerce\Models\Address;
use App\Modules\Ecommerce\Models\Cart;
use App\Modules\Ecommerce\Models\DeliveryZone;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;
use App\Modules\Ecommerce\Models\PromoCode;
use App\Modules\Ecommerce\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = consenting(User::factory()->create());
});

/** Prépare un panier de $quantity articles et une adresse à Cocody (frais : 2000 FCFA). */
function readyToOrder(User $user, int $price = 5000, int $stock = 10, int $quantity = 2): array
{
    DeliveryZone::firstOrCreate(
        ['commune' => 'Cocody'],
        ['fee_fcfa' => 2000, 'estimated_delay' => '24 à 48 h', 'is_active' => true],
    );

    $address = Address::factory()->for($user)->create(['commune' => 'Cocody', 'is_default' => true]);
    $variant = ProductVariant::factory()->for(Product::factory()->create())->create(['price_fcfa' => $price, 'stock' => $stock]);

    $cart = Cart::factory()->create(['user_id' => $user->id, 'token' => null]);
    $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => $quantity]);

    return [$address, $variant, $cart];
}

it('exige une connexion', function () {
    $this->postJson('/api/checkout', [])->assertUnauthorized();
});

it('crée la commande : montants, copie des prix, stock réservé, panier vidé, promo consommée', function () {
    [$address, $variant, $cart] = readyToOrder($this->user, 5000, 10, 2);
    $promo = PromoCode::factory()->create(['code' => 'BIENVENUE10', 'type' => 'percent', 'value' => 10]);
    $cart->update(['promo_code_id' => $promo->id]);

    Notification::fake();

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'card', 'note' => 'Appeler avant'])
        ->assertCreated()
        ->assertJsonPath('data.status.code', 'pending_payment')
        ->assertJsonPath('data.subtotal_fcfa', 10000)
        ->assertJsonPath('data.discount_fcfa', 1000)
        ->assertJsonPath('data.delivery_fee_fcfa', 2000)
        ->assertJsonPath('data.total_fcfa', 11000)
        ->assertJsonPath('data.promo_code', 'BIENVENUE10')
        ->assertJsonCount(1, 'data.items');

    expect($response->json('data.number'))->toBe('CMD-'.now()->year.'-000001')
        ->and($variant->fresh()->stock)->toBe(8)
        ->and($cart->fresh()->items()->count())->toBe(0)
        ->and($cart->fresh()->promo_code_id)->toBeNull()
        ->and($promo->fresh()->uses_count)->toBe(1)
        ->and(Order::first()->expires_at)->not->toBeNull();

    Notification::assertSentTo($this->user, OrderStatusNotification::class);
});

it("n'impose pas de délai de paiement pour un paiement à la livraison", function () {
    [$address] = readyToOrder($this->user);

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'cash_on_delivery'])
        ->assertCreated();

    expect(Order::first()->expires_at)->toBeNull();
});

it('refuse un panier vide', function () {
    [$address, , $cart] = readyToOrder($this->user);
    $cart->items()->delete();

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'card'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cart');
});

it('refuse un stock insuffisant sans rien créer ni toucher au stock', function () {
    [$address, $variant] = readyToOrder($this->user, 5000, 1, 3);

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'card'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cart');

    expect(Order::count())->toBe(0)->and($variant->fresh()->stock)->toBe(1);
});

it('refuse une commune qui n\'est plus desservie', function () {
    [$address] = readyToOrder($this->user);
    DeliveryZone::where('commune', 'Cocody')->update(['is_active' => false]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'card'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('address_id');
});

it("refuse l'adresse d'un autre client et un mode de paiement inconnu", function () {
    [$address] = readyToOrder($this->user);
    $foreign = Address::factory()->create();

    $this->actingAs($this->user, 'sanctum');

    $this->postJson('/api/checkout', ['address_id' => $foreign->id, 'payment_method' => 'card'])
        ->assertUnprocessable()->assertJsonValidationErrors('address_id');

    $this->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'bitcoin'])
        ->assertUnprocessable()->assertJsonValidationErrors('payment_method');
});

it('un double clic ne crée pas deux commandes', function () {
    [$address] = readyToOrder($this->user);
    $payload = ['address_id' => $address->id, 'payment_method' => 'card'];

    $this->actingAs($this->user, 'sanctum');

    $this->postJson('/api/checkout', $payload)->assertCreated();
    $this->postJson('/api/checkout', $payload)->assertUnprocessable()->assertJsonValidationErrors('cart');

    expect(Order::count())->toBe(1);
});

it('retire un code promo expiré du panier et demande de revérifier le total', function () {
    [$address, , $cart] = readyToOrder($this->user);
    $promo = PromoCode::factory()->create(['ends_at' => now()->subHour()]);
    $cart->update(['promo_code_id' => $promo->id]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'card'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    expect($cart->fresh()->promo_code_id)->toBeNull()->and(Order::count())->toBe(0);
});

it('garde le prix payé même si le catalogue change ensuite', function () {
    [$address, $variant] = readyToOrder($this->user, 5000, 10, 2);

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'card'])
        ->assertCreated();

    $variant->update(['price_fcfa' => 9999, 'name' => 'Renommée']);
    $address->delete();

    $number = Order::first()->number;

    $this->getJson("/api/orders/{$number}")
        ->assertOk()
        ->assertJsonPath('data.total_fcfa', 12000)
        ->assertJsonPath('data.items.0.unit_price_fcfa', 5000)
        ->assertJsonPath('data.shipping.commune', 'Cocody');
});
