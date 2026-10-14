<?php

namespace App\Modules\Ecommerce\Services;

use App\Models\User;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Billing\Services\SequenceService;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Exceptions\PromoCodeException;
use App\Modules\Ecommerce\Models\Cart;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private CartPricingService $pricing,
        private OrderStockService $stock,
        private PromoCodeService $promos,
        private SequenceService $sequences,
        private AuditLogger $audit,
    ) {}

    /**
     * Transforme le panier du client en commande.
     * Tout se passe dans UNE transaction : le stock, le code promo, la commande et le vidage
     * du panier réussissent ensemble ou pas du tout.
     *
     * @throws ValidationException
     */
    public function placeOrder(User $user, int $addressId, PaymentMethod $method, ?string $note = null): Order
    {
        $address = $user->addresses()->findOrFail($addressId);

        // Aperçu HORS transaction : si le code promo est devenu invalide, il est retiré du panier
        // (ce retrait doit rester enregistré), puis on demande au client de revérifier son total.
        $current = Cart::where('user_id', $user->id)->first();

        if ($current) {
            $preview = $this->pricing->summary($current, $address->commune);

            if ($preview['promo_error']) {
                throw ValidationException::withMessages([
                    'code' => [$preview['promo_error'].' Votre total a été mis à jour.'],
                ]);
            }
        }

        $order = DB::transaction(function () use ($user, $address, $method, $note) {
            // Le verrou du panier fait attendre un deuxième clic « Commander » : il trouvera un panier vide.
            $cart = Cart::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $cart || ! $cart->items()->exists()) {
                throw ValidationException::withMessages(['cart' => ['Votre panier est vide.']]);
            }

            $summary = $this->pricing->summary($cart, $address->commune);

            if ($summary['delivery'] === null) {
                throw ValidationException::withMessages(['address_id' => [$summary['delivery_error']]]);
            }

            if ($summary['promo_error']) {
                throw ValidationException::withMessages(['code' => [$summary['promo_error']]]);
            }

            if ($summary['has_issues']) {
                throw ValidationException::withMessages([
                    'cart' => ['Certains articles ne sont plus disponibles dans la quantité demandée. Vérifiez votre panier.'],
                ]);
            }

            if ($cart->promoCode) {
                try {
                    $this->promos->redeem($cart->promoCode);
                } catch (PromoCodeException $e) {
                    throw ValidationException::withMessages(['code' => [$e->getMessage()]]);
                }
            }

            $this->stock->reserve(collect($summary['lines'])->pluck('quantity', 'variant_id')->all());

            $order = Order::create([
                'number' => $this->sequences->next('order', 'CMD'),
                'user_id' => $user->id,
                'status' => OrderStatus::PendingPayment,
                'payment_method' => $method,
                'subtotal_fcfa' => $summary['subtotal_fcfa'],
                'discount_fcfa' => $summary['discount_fcfa'],
                'delivery_fee_fcfa' => $summary['delivery_fee_fcfa'],
                'total_fcfa' => $summary['total_fcfa'],
                'promo_code_id' => $cart->promo_code_id,
                'promo_code' => $summary['promo']['code'] ?? null,
                'ship_recipient_name' => $address->recipient_name,
                'ship_phone' => $address->phone,
                'ship_commune' => $address->commune,
                'ship_address_line' => $address->address_line,
                'ship_additional_info' => $address->additional_info,
                'customer_note' => $note,
                'expires_at' => $method->isOnline()
                    ? now()->addMinutes((int) config('shop.unpaid_order_ttl_minutes', 30))
                    : null,
            ]);

            foreach ($summary['lines'] as $line) {
                $order->items()->create([
                    'product_variant_id' => $line['variant_id'],
                    'product_name' => $line['product_name'],
                    'variant_name' => $line['variant_name'],
                    'unit_price_fcfa' => $line['unit_price_fcfa'],
                    'quantity' => $line['quantity'],
                    'line_total_fcfa' => $line['line_total_fcfa'],
                ]);
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::PendingPayment,
                'user_id' => $user->id,
            ]);

            $this->audit->log('orders.placed', 'shop', $order, [
                'total_fcfa' => $order->total_fcfa,
                'payment_method' => $method->value,
            ], $user);

            $cart->items()->delete();
            $cart->update(['promo_code_id' => null]);

            return $order;
        });

        $user->notify(new OrderStatusNotification($order, OrderStatus::PendingPayment));

        return $order->load('items');
    }
}
