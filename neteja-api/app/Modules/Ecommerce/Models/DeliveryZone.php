<?php

namespace App\Modules\Ecommerce\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = ['commune', 'fee_fcfa', 'estimated_delay', 'is_active'];

    protected function casts(): array
    {
        return ['fee_fcfa' => 'integer', 'is_active' => 'boolean'];
    }

    protected static function newFactory(): DeliveryZoneFactory
    {
        return DeliveryZoneFactory::new();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
