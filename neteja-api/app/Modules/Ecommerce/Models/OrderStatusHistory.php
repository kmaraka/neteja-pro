<?php

namespace App\Modules\Ecommerce\Models;

use App\Models\User;
use App\Modules\Ecommerce\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null; // une ligne d'historique ne se modifie jamais

    protected $fillable = ['order_id', 'from_status', 'to_status', 'user_id', 'note'];

    protected function casts(): array
    {
        return ['from_status' => OrderStatus::class, 'to_status' => OrderStatus::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
