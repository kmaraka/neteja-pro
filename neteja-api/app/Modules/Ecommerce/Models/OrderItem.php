<?php

namespace App\Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_variant_id', 'product_name', 'variant_name',
        'unit_price_fcfa', 'quantity', 'line_total_fcfa',
    ];

    protected function casts(): array
    {
        return ['unit_price_fcfa' => 'integer', 'quantity' => 'integer', 'line_total_fcfa' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
