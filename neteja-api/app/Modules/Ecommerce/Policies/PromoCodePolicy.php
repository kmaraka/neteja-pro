<?php

namespace App\Modules\Ecommerce\Policies;

use App\Models\User;
use App\Modules\Ecommerce\Models\PromoCode;

class PromoCodePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('promotions.view');
    }

    public function view(User $actor, PromoCode $promo): bool
    {
        return $actor->can('promotions.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('promotions.create');
    }

    public function update(User $actor, PromoCode $promo): bool
    {
        return $actor->can('promotions.update');
    }

    // Un code se désactive : on garde la trace des commandes qui l'ont utilisé.
    public function delete(User $actor, PromoCode $promo): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
