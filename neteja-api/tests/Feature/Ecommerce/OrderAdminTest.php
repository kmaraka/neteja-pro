<?php

use App\Models\User;
use App\Modules\Ecommerce\Enums\OrderStatus;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function orderStaff(string $role): User
{
    $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $user->assignRole($role);

    return $user;
}

it('ouvre la liste et le détail des commandes pour un administrateur', function () {
    $order = makeOrder();
    $order->statusHistories()->create(['from_status' => null, 'to_status' => OrderStatus::PendingPayment]);

    $this->actingAs(orderStaff('admin'));

    $this->get('/admin/orders')->assertOk()->assertSee($order->number);
    $this->get("/admin/orders/{$order->id}")->assertOk()->assertSee('Eau de Javel');
});

it('refuse les commandes à un client', function () {
    $this->actingAs(orderStaff('client'))->get('/admin/orders')->assertForbidden();
});
