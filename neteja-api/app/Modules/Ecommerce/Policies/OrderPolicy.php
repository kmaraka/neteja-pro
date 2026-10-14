<?php

namespace App\Modules\Ecommerce\Policies;

use App\Models\User;
use App\Modules\Ecommerce\Models\Order;

class OrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('orders.view');
    }

    public function view(User $actor, Order $order): bool
    {
        return $actor->can('orders.view');
    }

    // Les commandes sont créées par les clients, jamais par l'administration.
    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, Order $order): bool
    {
        return $actor->can('orders.update');
    }

    public function delete(User $actor, Order $order): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
