<?php

use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Support\Facades\Notification;

it('annule les commandes en ligne expirées et rend leur stock, sans toucher aux autres', function () {
    Notification::fake();

    $expired = makeOrder(null, ['expires_at' => now()->subMinutes(5)], quantity: 2, stock: 8);
    $recent = makeOrder(null, ['expires_at' => now()->addMinutes(20)]);
    $cash = makeOrder(null, ['payment_method' => PaymentMethod::CashOnDelivery, 'expires_at' => null]);
    $paid = makeOrder(null, ['status' => OrderStatus::Paid, 'expires_at' => null]);

    $this->artisan('shop:expire-unpaid-orders')->assertSuccessful();

    expect($expired->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(ProductVariant::find($expired->items->first()->product_variant_id)->stock)->toBe(10)
        ->and($recent->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($cash->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($paid->fresh()->status)->toBe(OrderStatus::Paid);
});
