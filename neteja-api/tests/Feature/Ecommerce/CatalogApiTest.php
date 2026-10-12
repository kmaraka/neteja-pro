<?php

use App\Modules\Ecommerce\Models\Category;
use App\Modules\Ecommerce\Models\DeliveryZone;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;

it('liste uniquement les produits en ligne qui ont une variante active', function () {
    $visible = Product::factory()->create(['name' => 'Javel visible']);
    ProductVariant::factory()->for($visible)->create();

    $offline = Product::factory()->create(['is_active' => false]);
    ProductVariant::factory()->for($offline)->create();

    Product::factory()->create(); // aucune variante

    $inactiveVariant = Product::factory()->create();
    ProductVariant::factory()->for($inactiveVariant)->create(['is_active' => false]);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Javel visible');
});

it("masque les produits d'une catégorie désactivée", function () {
    $category = Category::factory()->create(['is_active' => false]);
    $product = Product::factory()->for($category)->create();
    ProductVariant::factory()->for($product)->create();

    $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');
});

it('filtre par catégorie', function () {
    $entretien = Category::factory()->create(['slug' => 'entretien']);
    $jardin = Category::factory()->create(['slug' => 'jardin']);

    foreach ([$entretien, $jardin] as $category) {
        $product = Product::factory()->for($category)->create();
        ProductVariant::factory()->for($product)->create();
    }

    $this->getJson('/api/products?category=entretien')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category.slug', 'entretien');
});

it('recherche par nom', function () {
    foreach (['Eau de Javel', 'Balai-brosse'] as $name) {
        $product = Product::factory()->create(['name' => $name]);
        ProductVariant::factory()->for($product)->create();
    }

    $this->getJson('/api/products?search=javel')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Eau de Javel');
});

it('trie par prix', function () {
    $cher = Product::factory()->create(['name' => 'Cher']);
    ProductVariant::factory()->for($cher)->create(['price_fcfa' => 5000]);
    $pasCher = Product::factory()->create(['name' => 'Pas cher']);
    ProductVariant::factory()->for($pasCher)->create(['price_fcfa' => 1000]);

    $this->getJson('/api/products?sort=price_asc')->assertJsonPath('data.0.name', 'Pas cher');
    $this->getJson('/api/products?sort=price_desc')->assertJsonPath('data.0.name', 'Cher');
});

it('refuse un tri inconnu', function () {
    $this->getJson('/api/products?sort=n_importe_quoi')->assertUnprocessable()->assertJsonValidationErrors('sort');
});

it('pagine les résultats', function () {
    Product::factory()->count(15)->create()->each(fn ($product) => ProductVariant::factory()->for($product)->create());

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('meta.total', 15);
});

it('affiche la fiche produit avec ses variantes actives et masque le stock exact', function () {
    $product = Product::factory()->create(['slug' => 'eau-de-javel']);
    ProductVariant::factory()->for($product)->create(['name' => '5 L', 'stock' => 20]);
    ProductVariant::factory()->for($product)->create(['name' => '1 L', 'stock' => 3]);
    ProductVariant::factory()->for($product)->create(['name' => 'Rupture', 'stock' => 0]);
    ProductVariant::factory()->for($product)->create(['name' => 'Retirée', 'stock' => 9, 'is_active' => false]);

    $this->getJson('/api/products/eau-de-javel')
        ->assertOk()
        ->assertJsonCount(3, 'data.variants')
        ->assertJsonPath('data.variants.0.stock_left', null)   // stock confortable : non révélé
        ->assertJsonPath('data.variants.1.stock_left', 3)      // stock faible : révélé
        ->assertJsonPath('data.variants.2.in_stock', false);
});

it('renvoie 404 pour un produit hors ligne ou inconnu', function () {
    $product = Product::factory()->create(['slug' => 'cache', 'is_active' => false]);
    ProductVariant::factory()->for($product)->create();

    $this->getJson('/api/products/cache')->assertNotFound();
    $this->getJson('/api/products/inconnu')->assertNotFound();
});

it('liste seulement les catégories visibles et non vides', function () {
    $full = Category::factory()->create(['name' => 'Entretien']);
    $product = Product::factory()->for($full)->create();
    ProductVariant::factory()->for($product)->create();

    Category::factory()->create(['name' => 'Vide']);
    Category::factory()->create(['name' => 'Cachée', 'is_active' => false]);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Entretien')
        ->assertJsonPath('data.0.products_count', 1);
});

it('liste les zones de livraison actives par ordre alphabétique', function () {
    DeliveryZone::factory()->create(['commune' => 'Yopougon']);
    DeliveryZone::factory()->create(['commune' => 'Cocody']);
    DeliveryZone::factory()->create(['commune' => 'Bingerville', 'is_active' => false]);

    $this->getJson('/api/delivery-zones')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.commune', 'Cocody');
});
