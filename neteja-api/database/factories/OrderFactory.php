<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'number' => 'CMD-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory(),
            'status' => OrderStatus::PendingPayment,
            'payment_method' => PaymentMethod::Card,
            'subtotal_fcfa' => 10000,
            'discount_fcfa' => 0,
            'delivery_fee_fcfa' => 2000,
            'total_fcfa' => 12000,
            'ship_recipient_name' => fake()->name(),
            'ship_phone' => '+2250700000000',
            'ship_commune' => 'Cocody',
            'ship_address_line' => fake()->streetAddress(),
        ];
    }
}
