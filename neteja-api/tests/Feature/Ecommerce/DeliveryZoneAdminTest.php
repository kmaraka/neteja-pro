<?php

use App\Models\User;
use App\Modules\Ecommerce\Models\DeliveryZone;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function shopStaff(string $role): User
{
    $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $user->assignRole($role);

    return $user;
}

it('ouvre les écrans des zones de livraison pour un administrateur', function () {
    $zone = DeliveryZone::factory()->create(['commune' => 'Cocody']);

    $this->actingAs(shopStaff('admin'));

    $this->get('/admin/delivery-zones')->assertOk()->assertSee('Cocody');
    $this->get('/admin/delivery-zones/create')->assertOk();
    $this->get("/admin/delivery-zones/{$zone->id}/edit")->assertOk();
});

it('refuse les zones de livraison à un client', function () {
    $this->actingAs(shopStaff('client'))->get('/admin/delivery-zones')->assertForbidden();
});
