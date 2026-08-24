<?php

namespace App\Policies;

use App\Models\User;

class ProductPolicy
{
    /**
     * Determine if user can create products
     * Only super admin can manage products
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can update products
     * Only super admin can manage products
     */
    public function update(User $user, $product = null): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can delete products
     * Only super admin can delete products
     */
    public function delete(User $user, $product = null): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can view products
     * All authenticated users can view products
     */
    public function view(User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can restore products
     */
    public function restore(User $user, $product = null): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can permanently delete products
     */
    public function forceDelete(User $user, $product = null): bool
    {
        return $user->isSuperAdmin();
    }
}
