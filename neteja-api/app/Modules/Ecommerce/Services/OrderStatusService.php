<?php

namespace App\Modules\Ecommerce\Services;

use App\Models\User;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Exceptions\InvalidOrderTransition;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Models\PromoCode;
use App\Modules\Ecommerce\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function __construct(
        private OrderStockService $stock,
        private PromoCodeService $promos,
        private AuditLogger $audit,
    ) {}

    /**
     * Change le statut d'une commande, en appliquant les règles et leurs conséquences
     * (dates, stock rendu, code promo libéré, historique, journal d'audit, notification).
     *
     * @throws InvalidOrderTransition
     */
    public function transition(Order $order, OrderStatus $to, ?User $actor = null, ?string $note = null): Order
    {
        $updated = DB::transaction(function () use ($order, $to, $actor, $note) {
            // Verrou : deux changements simultanés sur la même commande ne s'écrasent pas.
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! in_array($to, $from->allowedNext($locked->payment_method), true)) {
                throw new InvalidOrderTransition("Passage impossible : « {$from->label()} » vers « {$to->label()} ».");
            }

            $attributes = ['status' => $to];

            $dateColumns = [
                OrderStatus::Paid->value => 'paid_at',
                OrderStatus::Shipped->value => 'shipped_at',
                OrderStatus::Delivered->value => 'delivered_at',
                OrderStatus::Cancelled->value => 'cancelled_at',
            ];

            if (isset($dateColumns[$to->value])) {
                $attributes[$dateColumns[$to->value]] = now();
            }

            // Paiement à la livraison : l'argent est encaissé à la remise du colis.
            if ($to === OrderStatus::Delivered && $locked->payment_method === PaymentMethod::CashOnDelivery) {
                $attributes['paid_at'] = now();
            }

            // Plus de compte à rebours dès que la commande n'est plus « en attente de paiement ».
            $attributes['expires_at'] = null;

            // Le stock est rendu si la commande est annulée, ou remboursée avant expédition.
            $restock = $to === OrderStatus::Cancelled
                || ($to === OrderStatus::Refunded && in_array($from, [OrderStatus::Paid, OrderStatus::Preparing], true));

            if ($restock) {
                $this->stock->release($locked);
            }

            // Un code promo n'est libéré que pour une commande annulée.
            if ($to === OrderStatus::Cancelled && $locked->promo_code_id) {
                $promo = PromoCode::find($locked->promo_code_id);

                if ($promo) {
                    $this->promos->release($promo);
                }
            }

            $locked->forceFill($attributes)->save();

            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $actor?->id,
                'note' => $note,
            ]);

            $this->audit->log('orders.status_changed', 'shop', $locked, [
                'from' => $from->value,
                'to' => $to->value,
                'note' => $note,
            ], $actor);

            return $locked;
        });

        // Après validation de la transaction : on ne prévient pas le client d'un changement annulé.
        $updated->user->notify(new OrderStatusNotification($updated, $to));

        return $updated;
    }
}
