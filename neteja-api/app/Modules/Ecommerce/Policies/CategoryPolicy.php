<?php

namespace App\Modules\Ecommerce\Policies;

use App\Models\User;
use App\Modules\Ecommerce\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('catalog.view');
    }

    public function view(User $actor, Category $category): bool
    {
        return $actor->can('catalog.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('catalog.create');
    }

    public function update(User $actor, Category $category): bool
    {
        return $actor->can('catalog.update');
    }

    public function delete(User $actor, Category $category): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
