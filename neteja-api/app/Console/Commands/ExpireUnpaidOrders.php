<?php

namespace App\Console\Commands;

use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Exceptions\InvalidOrderTransition;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Services\OrderStatusService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'shop:expire-unpaid-orders';

    protected $description = 'Annule les commandes à payer en ligne non réglées à temps et rend leur stock';

    public function handle(OrderStatusService $statuses): int
    {
        $count = 0;

        $expired = Order::query()
            ->where('status', OrderStatus::PendingPayment->value)
            ->whereNotNull('expires_at') // les commandes à la livraison n'expirent pas
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $order) {
            try {
                $statuses->transition($order, OrderStatus::Cancelled, null, 'Paiement non reçu à temps');
                $count++;
            } catch (InvalidOrderTransition) {
                // Le statut a changé entre-temps (paiement reçu) : rien à faire.
            }
        }

        $this->info("{$count} commande(s) expirée(s) annulée(s).");

        return self::SUCCESS;
    }
}
