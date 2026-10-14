<?php

namespace App\Modules\Ecommerce\Models;

use App\Models\User;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $table = 'product_orders';

    protected $fillable = [
        'number', 'user_id', 'status', 'payment_method',
        'subtotal_fcfa', 'discount_fcfa', 'delivery_fee_fcfa', 'total_fcfa',
        'promo_code_id', 'promo_code',
        'ship_recipient_name', 'ship_phone', 'ship_commune', 'ship_address_line', 'ship_additional_info',
        'customer_note', 'expires_at', 'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal_fcfa' => 'integer',
            'discount_fcfa' => 'integer',
            'delivery_fee_fcfa' => 'integer',
            'total_fcfa' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    /** Le client ne peut annuler lui-même qu'avant tout paiement. */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }
}
