<?php

namespace App\Modules\Ecommerce\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'En attente de paiement',
            self::Paid => 'Payée',
            self::Preparing => 'En préparation',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
            self::Refunded => 'Remboursée',
        };
    }

    /** Couleur d'affichage dans Filament. */
    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Paid, self::Preparing => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'gray',
            self::Refunded => 'danger',
        };
    }

    /**
     * Statuts vers lesquels on peut passer depuis celui-ci.
     *
     * @return list<self>
     */
    public function allowedNext(PaymentMethod $method): array
    {
        $cash = $method === PaymentMethod::CashOnDelivery;

        return match ($this) {
            // Paiement à la livraison : confirmation par téléphone, puis préparation directe.
            self::PendingPayment => $cash ? [self::Preparing, self::Cancelled] : [self::Paid, self::Cancelled],
            self::Paid => [self::Preparing, self::Cancelled, self::Refunded],
            // Rien n'a été encaissé à la livraison : pas de remboursement avant la livraison.
            self::Preparing => $cash ? [self::Shipped, self::Cancelled] : [self::Shipped, self::Cancelled, self::Refunded],
            self::Shipped => [self::Delivered],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }
}
