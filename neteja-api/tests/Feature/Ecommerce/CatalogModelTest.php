<?php

use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Database\QueryException;

it('calcule le prix de départ et le stock des variantes actives seulement', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['name' => '5 L', 'price_fcfa' => 6500, 'stock' => 3]);
    ProductVariant::factory()->for($product)->create(['name' => '1 L', 'price_fcfa' => 1500, 'stock' => 5]);
    ProductVariant::factory()->for($product)->create(['name' => 'Ancien', 'price_fcfa' => 500, 'stock' => 9, 'is_active' => false]);

    $product->load('variants');

    expect($product->priceFrom())->toBe(1500)
        ->and($product->stockTotal())->toBe(8)
        ->and($product->isInStock())->toBeTrue();
});

it('signale un produit sans stock', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['stock' => 0]);

    $product->load('variants');

    expect($product->isInStock())->toBeFalse();
});

it('ne retourne que les produits en ligne avec le scope active', function () {
    Product::factory()->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]);

    expect(Product::active()->count())->toBe(1);
});

it('refuse deux produits avec la même adresse (slug)', function () {
    Product::factory()->create(['slug' => 'javel']);

    expect(fn () => Product::factory()->create(['slug' => 'javel']))->toThrow(QueryException::class);
});

it('ne permet pas de supprimer une catégorie qui contient des produits', function () {
    $product = Product::factory()->create();

    expect(fn () => $product->category->delete())->toThrow(QueryException::class);
});
