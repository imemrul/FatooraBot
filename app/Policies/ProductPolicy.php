<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('view_inventory');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id
            && $user->canAccess('view_inventory');
    }

    public function create(User $user): bool
    {
        return $user->canAccess('manage_inventory');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id
            && $user->canAccess('manage_inventory');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id
            && $user->canAccess('manage_inventory');
    }
}
