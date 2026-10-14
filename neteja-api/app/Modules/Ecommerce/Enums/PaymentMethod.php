<?php

namespace App\Modules\Ecommerce\Enums;

enum PaymentMethod: string
{
    case Card = 'card';
    case MobileMoney = 'mobile_money';
    case CashOnDelivery = 'cash_on_delivery';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Carte bancaire',
            self::MobileMoney => 'Mobile Money',
            self::CashOnDelivery => 'Paiement à la livraison',
        };
    }

    /** Paiement en ligne : la commande doit être réglée dans un délai limité. */
    public function isOnline(): bool
    {
        return $this !== self::CashOnDelivery;
    }
}
