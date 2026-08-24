<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if user can view orders
     */
    public function view(User $user, Order $order): bool
    {
        // Super admin can view all orders
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch manager/sales user can view their branch's orders only
        return $user->branch_id === $order->branch_id;
    }

    /**
     * Determine if user can create orders
     */
    public function create(User $user): bool
    {
        // Branch manager and sales user can create orders
        if ($user->isBranchManager() || $user->isSalesUser()) {
            return true;
        }

        // Super admin cannot create orders directly (no branch assigned)
        return false;
    }

    /**
     * Determine if user can cancel orders
     */
    public function cancel(User $user, Order $order): bool
    {
        // Super admin can cancel any order
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch manager can cancel orders in their branch
        return $user->isBranchManager() && $user->branch_id === $order->branch_id;
    }
}
