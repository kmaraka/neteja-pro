<?php

namespace App\Modules\Ecommerce\Policies;

use App\Models\User;
use App\Modules\Ecommerce\Models\DeliveryZone;

class DeliveryZonePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('catalog.view');
    }

    public function view(User $actor, DeliveryZone $zone): bool
    {
        return $actor->can('catalog.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('catalog.create');
    }

    public function update(User $actor, DeliveryZone $zone): bool
    {
        return $actor->can('catalog.update');
    }

    // Une zone se désactive, elle ne se supprime pas.
    public function delete(User $actor, DeliveryZone $zone): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
