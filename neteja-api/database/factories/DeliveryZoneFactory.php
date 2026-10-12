<?php

namespace Database\Factories;

use App\Modules\Ecommerce\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryZone> */
class DeliveryZoneFactory extends Factory
{
    protected $model = DeliveryZone::class;

    public function definition(): array
    {
        return [
            'commune' => fake()->unique()->city(),
            'fee_fcfa' => fake()->numberBetween(10, 40) * 100,
            'estimated_delay' => '24 à 48 h',
            'is_active' => true,
        ];
    }
}
