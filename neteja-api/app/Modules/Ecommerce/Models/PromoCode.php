<?php

namespace App\Modules\Ecommerce\Models;

use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code', 'type', 'value', 'starts_at', 'ends_at', 'max_uses', 'uses_count', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): PromoCodeFactory
    {
        return PromoCodeFactory::new();
    }

    // Un code est toujours enregistré en majuscules, sans espaces autour.
    protected function code(): Attribute
    {
        return Attribute::make(set: fn (string $value) => strtoupper(trim($value)));
    }
}
