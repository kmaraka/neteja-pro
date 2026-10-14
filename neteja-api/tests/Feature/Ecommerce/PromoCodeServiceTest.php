<?php

use App\Modules\Ecommerce\Exceptions\PromoCodeException;
use App\Modules\Ecommerce\Models\PromoCode;
use App\Modules\Ecommerce\Services\PromoCodeService;

beforeEach(fn () => $this->service = app(PromoCodeService::class));

it('retrouve un code sans tenir compte des majuscules ni des espaces', function () {
    PromoCode::factory()->create(['code' => 'bienvenue10']);

    expect($this->service->find('  Bienvenue10 ')?->code)->toBe('BIENVENUE10');
});

it('calcule une remise en pourcentage arrondie vers le bas', function () {
    $promo = PromoCode::factory()->create(['type' => 'percent', 'value' => 15]);

    expect($this->service->discountFor($promo, 10000))->toBe(1500)
        ->and($this->service->discountFor($promo, 1999))->toBe(299); // 299,85 arrondi à 299
});

it('plafonne une remise fixe au sous-total', function () {
    $promo = PromoCode::factory()->create(['type' => 'fixed', 'value' => 5000]);

    expect($this->service->discountFor($promo, 3000))->toBe(3000)
        ->and($this->service->discountFor($promo, 20000))->toBe(5000);
});

it('refuse un code inactif, expiré, pas encore valide ou épuisé', function (array $state, string $message) {
    $promo = PromoCode::factory()->create($state);

    expect(fn () => $this->service->discountFor($promo, 10000))
        ->toThrow(PromoCodeException::class, $message);
})->with([
    'inactif' => [['is_active' => false], "n'est plus valide"],
    'expiré' => [['ends_at' => now()->subDay()], 'a expiré'],
    'futur' => [['starts_at' => now()->addDay()], 'pas encore valide'],
    'épuisé' => [['max_uses' => 5, 'uses_count' => 5], "limite d'utilisation"],
]);

it("consomme une utilisation et s'arrête au plafond", function () {
    $promo = PromoCode::factory()->create(['max_uses' => 1]);

    $this->service->redeem($promo);

    expect($promo->fresh()->uses_count)->toBe(1)
        ->and(fn () => $this->service->redeem($promo))->toThrow(PromoCodeException::class);

    expect($promo->fresh()->uses_count)->toBe(1);
});
