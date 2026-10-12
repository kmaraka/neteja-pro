<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Ecommerce\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Address> */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Maison',
            'recipient_name' => fake()->name(),
            'phone' => '+22507'.fake()->numerify('########'),
            'commune' => 'Cocody',
            'address_line' => fake()->streetAddress(),
            'additional_info' => null,
            'is_default' => false,
        ];
    }
}
