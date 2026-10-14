<?php

namespace Database\Factories;

use App\Modules\Ecommerce\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PromoCode> */
class PromoCodeFactory extends Factory
{
    protected $model = PromoCode::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PROMO-####'),
            'type' => PromoCode::TYPE_PERCENT,
            'value' => 10,
            'starts_at' => null,
            'ends_at' => null,
            'max_uses' => null,
            'uses_count' => 0,
            'is_active' => true,
        ];
    }
}
