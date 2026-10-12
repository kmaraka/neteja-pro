<?php

use App\Models\User;
use App\Modules\Ecommerce\Models\Category;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

/** Compte avec rôle et 2FA activé : on évite la redirection vers la configuration du 2FA. */
function catalogStaff(string $role): User
{
    $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $user->assignRole($role);

    return $user;
}

it('donne les droits du catalogue à un admin mais pas à un client', function () {
    $admin = catalogStaff('admin');
    $client = catalogStaff('client');

    expect($admin->can('create', Product::class))->toBeTrue()
        ->and($admin->can('update', Category::factory()->create()))->toBeTrue()
        ->and($client->can('viewAny', Product::class))->toBeFalse();
});

it('ouvre les écrans du catalogue pour un administrateur', function () {
    $this->actingAs(catalogStaff('admin'));

    $this->get('/admin/products')->assertOk();
    $this->get('/admin/products/create')->assertOk();
    $this->get('/admin/categories')->assertOk();
    $this->get('/admin/categories/create')->assertOk();
});

it('refuse les écrans du catalogue à un client', function () {
    $this->actingAs(catalogStaff('client'));

    $this->get('/admin/products')->assertForbidden();
});

it('affiche le formulaire de modification avec ses variantes', function () {
    $product = Product::factory()->create(['name' => 'Eau de Javel']);
    ProductVariant::factory()->for($product)->create(['name' => '5 L']);

    $this->actingAs(catalogStaff('admin'))
        ->get("/admin/products/{$product->id}/edit")
        ->assertOk()
        ->assertSee('Eau de Javel');
});
