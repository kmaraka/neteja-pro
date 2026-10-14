<?php

namespace Database\Factories;

use App\Modules\Ecommerce\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Cart> */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'token' => Str::random(40),
            'promo_code_id' => null,
            'last_activity_at' => now(),
        ];
    }
}
