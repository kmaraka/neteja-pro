<?php

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Exceptions\InvalidOrderTransition;
use App\Modules\Ecommerce\Models\ProductVariant;
use App\Modules\Ecommerce\Models\PromoCode;
use App\Modules\Ecommerce\Notifications\OrderStatusNotification;
use App\Modules\Ecommerce\Services\OrderStatusService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->service = app(OrderStatusService::class);
});

it('suit le cycle normal et renseigne les dates', function () {
    $order = makeOrder();

    foreach ([OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
        $order = $this->service->transition($order, $status);
    }

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->shipped_at)->not->toBeNull()
        ->and($order->delivered_at)->not->toBeNull()
        ->and($order->statusHistories()->count())->toBe(4);
});

it('refuse les passages interdits', function (OrderStatus $start, OrderStatus $target, PaymentMethod $method) {
    $order = makeOrder(null, ['status' => $start, 'payment_method' => $method]);

    expect(fn () => $this->service->transition($order, $target))->toThrow(InvalidOrderTransition::class);

    expect($order->fresh()->status)->toBe($start);
})->with([
    'livrée vers préparation' => [OrderStatus::Delivered, OrderStatus::Preparing, PaymentMethod::Card],
    'carte : saut vers préparation' => [OrderStatus::PendingPayment, OrderStatus::Preparing, PaymentMethod::Card],
    'livraison : jamais « payée »' => [OrderStatus::PendingPayment, OrderStatus::Paid, PaymentMethod::CashOnDelivery],
    'annulée est définitive' => [OrderStatus::Cancelled, OrderStatus::Paid, PaymentMethod::Card],
    'expédiée ne s\'annule pas' => [OrderStatus::Shipped, OrderStatus::Cancelled, PaymentMethod::Card],
]);

it('paiement à la livraison : confirmation, préparation, puis encaissement à la livraison', function () {
    $order = makeOrder(null, ['payment_method' => PaymentMethod::CashOnDelivery]);

    $order = $this->service->transition($order, OrderStatus::Preparing);
    expect($order->paid_at)->toBeNull();

    $order = $this->service->transition($order, OrderStatus::Shipped);
    $order = $this->service->transition($order, OrderStatus::Delivered);

    expect($order->paid_at)->not->toBeNull();
});

it('rend le stock et le code promo quand la commande est annulée', function () {
    $promo = PromoCode::factory()->create(['uses_count' => 1]);
    $order = makeOrder(null, ['promo_code_id' => $promo->id, 'promo_code' => $promo->code], quantity: 2, stock: 8);

    $this->service->transition($order, OrderStatus::Cancelled);

    expect(ProductVariant::first()->stock)->toBe(10)
        ->and($promo->fresh()->uses_count)->toBe(0)
        ->and($order->fresh()->cancelled_at)->not->toBeNull();
});

it('rend le stock pour un remboursement avant expédition mais pas après livraison', function () {
    $beforeShipping = makeOrder(null, ['status' => OrderStatus::Paid], quantity: 2, stock: 8);
    $this->service->transition($beforeShipping, OrderStatus::Refunded);
    expect(ProductVariant::find($beforeShipping->items->first()->product_variant_id)->stock)->toBe(10);

    $afterDelivery = makeOrder(null, ['status' => OrderStatus::Delivered], quantity: 2, stock: 8);
    $this->service->transition($afterDelivery, OrderStatus::Refunded);
    expect(ProductVariant::find($afterDelivery->items->first()->product_variant_id)->stock)->toBe(8);
});

it('journalise le changement et prévient le client', function () {
    $user = User::factory()->create();
    $order = makeOrder($user);

    $this->service->transition($order, OrderStatus::Paid, null, 'Virement reçu');

    expect(AuditLog::where('action', 'orders.status_changed')->count())->toBe(1)
        ->and($order->statusHistories()->first()->note)->toBe('Virement reçu');

    Notification::assertSentTo($user, OrderStatusNotification::class, fn ($n) => $n->status === OrderStatus::Paid);
});
