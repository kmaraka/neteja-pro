<?php

namespace App\Modules\Ecommerce\Notifications;

use App\Modules\Core\Notifications\NetejaNotification;
use App\Modules\Core\Support\Money;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class OrderStatusNotification extends NetejaNotification
{
    use SerializesModels;

    public function __construct(public Order $order, public OrderStatus $status) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande {$this->order->number} : {$this->status->label()}")
            ->greeting('Bonjour,')
            ->line($this->message())
            ->line('Montant de la commande : '.Money::fcfa($this->order->total_fcfa));
    }

    // Sans accents : un accent peut faire passer le SMS en UCS-2 (limite de 70 caractères).
    public function toSms(object $notifiable): string
    {
        return Str::ascii($this->message());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_number' => $this->order->number,
            'status' => $this->status->value,
            'message' => $this->message(),
        ];
    }

    private function message(): string
    {
        $number = $this->order->number;
        $total = Money::fcfa($this->order->total_fcfa);

        return match ($this->status) {
            OrderStatus::PendingPayment => $this->order->payment_method === PaymentMethod::CashOnDelivery
                ? "Commande {$number} reçue. Nous vous appellerons pour la confirmer. Total à régler à la livraison : {$total}."
                : "Commande {$number} reçue. Total : {$total}. Finalisez le paiement pour la valider.",
            OrderStatus::Paid => "Paiement reçu pour la commande {$number}. Merci !",
            OrderStatus::Preparing => "Votre commande {$number} est en préparation.",
            OrderStatus::Shipped => "Votre commande {$number} est en cours de livraison.",
            OrderStatus::Delivered => "Commande {$number} livrée. Merci de votre confiance !",
            OrderStatus::Cancelled => "Votre commande {$number} a été annulée.",
            OrderStatus::Refunded => "Votre commande {$number} a été remboursée.",
        };
    }
}
