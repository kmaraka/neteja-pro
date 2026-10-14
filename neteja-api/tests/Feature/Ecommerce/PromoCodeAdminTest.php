<?php

use App\Models\User;
use App\Modules\Ecommerce\Models\PromoCode;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function promoStaff(string $role): User
{
    $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $user->assignRole($role);

    return $user;
}

it('ouvre les écrans des codes promo pour un administrateur', function () {
    $promo = PromoCode::factory()->create(['code' => 'BIENVENUE10']);

    $this->actingAs(promoStaff('admin'));

    $this->get('/admin/promo-codes')->assertOk()->assertSee('BIENVENUE10');
    $this->get('/admin/promo-codes/create')->assertOk();
    $this->get("/admin/promo-codes/{$promo->id}/edit")->assertOk();
});

it('refuse les codes promo à un client', function () {
    $this->actingAs(promoStaff('client'))->get('/admin/promo-codes')->assertForbidden();
});
