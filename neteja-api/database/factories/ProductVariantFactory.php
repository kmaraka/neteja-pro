<?php

namespace Database\Factories;

use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Standard',
            'price_fcfa' => fake()->numberBetween(5, 100) * 100,
            'stock' => fake()->numberBetween(0, 50),
            'is_active' => true,
        ];
    }
}
