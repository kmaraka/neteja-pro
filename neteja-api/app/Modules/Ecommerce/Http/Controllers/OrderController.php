<?php

namespace App\Modules\Ecommerce\Http\Controllers;

use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Exceptions\InvalidOrderTransition;
use App\Modules\Ecommerce\Http\Requests\CheckoutRequest;
use App\Modules\Ecommerce\Http\Resources\CustomerOrderResource;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Services\CheckoutService;
use App\Modules\Ecommerce\Services\OrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class OrderController
{
    public function checkout(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        $order = $checkout->placeOrder(
            $request->user(),
            (int) $request->validated('address_id'),
            PaymentMethod::from($request->validated('payment_method')),
            $request->validated('note'),
        );

        return (new CustomerOrderResource($order))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return CustomerOrderResource::collection(
            Order::where('user_id', $request->user()->id)->with('items')->latest('id')->paginate(10)
        );
    }

    public function show(Request $request, string $number): CustomerOrderResource
    {
        return new CustomerOrderResource($this->own($request, $number)->load(['items', 'statusHistories']));
    }

    public function cancel(Request $request, string $number, OrderStatusService $statuses): CustomerOrderResource
    {
        $order = $this->own($request, $number);

        if (! $order->canBeCancelledByCustomer()) {
            throw ValidationException::withMessages([
                'order' => ['Cette commande ne peut plus être annulée en ligne. Contactez-nous.'],
            ]);
        }

        try {
            $order = $statuses->transition($order, OrderStatus::Cancelled, $request->user(), 'Annulée par le client');
        } catch (InvalidOrderTransition $e) {
            throw ValidationException::withMessages(['order' => [$e->getMessage()]]);
        }

        return new CustomerOrderResource($order->load(['items', 'statusHistories']));
    }

    /** Une commande d'un autre client donne un 404 : on ne révèle même pas qu'elle existe. */
    private function own(Request $request, string $number): Order
    {
        return Order::where('user_id', $request->user()->id)->where('number', $number)->firstOrFail();
    }
}
