<?php

use App\Models\User;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->user = consenting(User::factory()->create());
});

it('liste seulement mes commandes', function () {
    makeOrder($this->user);
    makeOrder($this->user);
    makeOrder(); // celle d'un autre client

    $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/orders')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it("affiche le détail d'une de mes commandes avec son historique", function () {
    $order = makeOrder($this->user);
    $order->statusHistories()->create(['from_status' => null, 'to_status' => OrderStatus::PendingPayment]);

    $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/orders/{$order->number}")
        ->assertOk()
        ->assertJsonPath('data.number', $order->number)
        ->assertJsonPath('data.can_cancel', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonCount(1, 'data.history');
});

it("renvoie 404 pour la commande d'un autre client", function () {
    $other = makeOrder();

    $this->actingAs($this->user, 'sanctum');

    $this->getJson("/api/orders/{$other->number}")->assertNotFound();
    $this->postJson("/api/orders/{$other->number}/cancel")->assertNotFound();
});

it('annule une commande en attente de paiement et rend le stock', function () {
    $order = makeOrder($this->user, quantity: 2, stock: 8);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/orders/{$order->number}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status.code', 'cancelled');

    expect(ProductVariant::first()->stock)->toBe(10);
});

it("refuse l'annulation en ligne d'une commande déjà payée", function () {
    $order = makeOrder($this->user, ['status' => OrderStatus::Paid]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/orders/{$order->number}/cancel")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('order');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});
