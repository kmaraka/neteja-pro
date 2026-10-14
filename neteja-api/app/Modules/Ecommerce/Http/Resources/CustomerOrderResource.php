<?php

namespace App\Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'status' => ['code' => $this->status->value, 'label' => $this->status->label()],
            'payment_method' => ['code' => $this->payment_method->value, 'label' => $this->payment_method->label()],
            'subtotal_fcfa' => $this->subtotal_fcfa,
            'discount_fcfa' => $this->discount_fcfa,
            'delivery_fee_fcfa' => $this->delivery_fee_fcfa,
            'total_fcfa' => $this->total_fcfa,
            'promo_code' => $this->promo_code,
            'shipping' => [
                'recipient_name' => $this->ship_recipient_name,
                'phone' => $this->ship_phone,
                'commune' => $this->ship_commune,
                'address_line' => $this->ship_address_line,
                'additional_info' => $this->ship_additional_info,
            ],
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'unit_price_fcfa' => $item->unit_price_fcfa,
                'quantity' => $item->quantity,
                'line_total_fcfa' => $item->line_total_fcfa,
            ])->values()),
            'history' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories->map(fn ($entry) => [
                'status' => ['code' => $entry->to_status->value, 'label' => $entry->to_status->label()],
                'at' => $entry->created_at->toIso8601String(),
            ])->values()),
            'can_cancel' => $this->canBeCancelledByCustomer(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
