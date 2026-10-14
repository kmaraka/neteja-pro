<?php

namespace App\Modules\Ecommerce\Services;

use App\Modules\Ecommerce\Exceptions\PromoCodeException;
use App\Modules\Ecommerce\Models\PromoCode;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    public function find(string $code): ?PromoCode
    {
        return PromoCode::where('code', strtoupper(trim($code)))->first();
    }

    /** @throws PromoCodeException */
    public function assertUsable(PromoCode $promo): void
    {
        if (! $promo->is_active) {
            throw new PromoCodeException("Ce code promo n'est plus valide.");
        }

        if ($promo->starts_at && $promo->starts_at->isFuture()) {
            throw new PromoCodeException("Ce code promo n'est pas encore valide.");
        }

        if ($promo->ends_at && $promo->ends_at->isPast()) {
            throw new PromoCodeException('Ce code promo a expiré.');
        }

        if ($promo->max_uses !== null && $promo->uses_count >= $promo->max_uses) {
            throw new PromoCodeException("Ce code promo a atteint sa limite d'utilisation.");
        }
    }

    /**
     * Montant de la remise en FCFA. Jamais supérieur au sous-total.
     *
     * @throws PromoCodeException
     */
    public function discountFor(PromoCode $promo, int $subtotal): int
    {
        $this->assertUsable($promo);

        $discount = $promo->type === PromoCode::TYPE_PERCENT
            ? intdiv($subtotal * $promo->value, 100) // arrondi vers le bas
            : $promo->value;

        return min($discount, $subtotal);
    }

    /**
     * Consomme une utilisation (appelée à la création d'une commande, en 2C).
     * Le verrou évite que deux commandes simultanées dépassent le plafond.
     *
     * @throws PromoCodeException
     */
    public function redeem(PromoCode $promo): void
    {
        DB::transaction(function () use ($promo) {
            $locked = PromoCode::whereKey($promo->id)->lockForUpdate()->firstOrFail();

            $this->assertUsable($locked);
            $locked->increment('uses_count');
        });
    }
}
