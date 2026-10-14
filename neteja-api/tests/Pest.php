<?php

use App\Models\User;
use App\Modules\Compliance\Services\ConsentService;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/** En-tête qui fait passer la requête pour une requête du front React (mode session). */
function spa(): array
{
    return ['Referer' => 'http://localhost'];
}

/** Un utilisateur qui a déjà accepté les CGU (pour tester le reste sans être bloqué). */
function consenting(User $user): User
{
    app(ConsentService::class)->accept($user, array_keys(config('legal.versions')), '127.0.0.1');

    return $user;
}

/** Une commande existante avec 1 article. Le stock de la variante est celui APRÈS réservation. */
function makeOrder(?User $user = null, array $attributes = [], int $quantity = 2, int $stock = 8): Order
{
    $variant = ProductVariant::factory()->for(Product::factory()->create())->create([
        'price_fcfa' => 5000,
        'stock' => $stock,
    ]);

    $order = Order::factory()->create(['user_id' => ($user ?? User::factory()->create())->id] + $attributes);

    $order->items()->create([
        'product_variant_id' => $variant->id,
        'product_name' => 'Eau de Javel',
        'variant_name' => '1 L',
        'unit_price_fcfa' => 5000,
        'quantity' => $quantity,
        'line_total_fcfa' => 5000 * $quantity,
    ]);

    return $order;
}
