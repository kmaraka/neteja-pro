<?php

namespace App\Modules\Ecommerce\Policies;

use App\Models\User;
use App\Modules\Ecommerce\Models\Product;

class ProductPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('catalog.view');
    }

    public function view(User $actor, Product $product): bool
    {
        return $actor->can('catalog.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('catalog.create');
    }

    public function update(User $actor, Product $product): bool
    {
        return $actor->can('catalog.update');
    }

    // Pas de suppression : un produit se désactive, pour garder l'historique des commandes.
    public function delete(User $actor, Product $product): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
