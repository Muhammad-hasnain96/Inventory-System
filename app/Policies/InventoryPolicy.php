<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine if user can view inventory
     * Admin can view all, Manager can view only their branch
     */
    public function view(User $user, Inventory $inventory): bool
    {
        // Super admin can view all inventories
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch manager can only view their branch's inventory
        if ($user->isBranchManager()) {
            return $user->branch_id === $inventory->branch_id;
        }

        // Sales users cannot view inventory details (only through orders)
        return false;
    }

    /**
     * Determine if user can add stock
     * Admin and Manager (own branch) can add stock
     */
    public function addStock(User $user, Inventory $inventory): bool
    {
        // Super admin can add stock to all inventories
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only branch manager can add stock to their branch
        if ($user->isBranchManager()) {
            return $user->branch_id === $inventory->branch_id;
        }

        // Sales users cannot add stock
        return false;
    }

    /**
     * Determine if user can adjust stock
     * Admin and Manager (own branch) can adjust stock
     */
    public function adjustStock(User $user, Inventory $inventory): bool
    {
        // Super admin can adjust stock in all inventories
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only branch manager can adjust stock in their branch
        if ($user->isBranchManager()) {
            return $user->branch_id === $inventory->branch_id;
        }

        // Sales users cannot adjust stock
        return false;
    }

    /**
     * Determine if user can transfer stock
     * Admin and Manager (own branch) can transfer stock
     */
    public function transfer(User $user, Inventory $inventory): bool
    {
        // Super admin can transfer stock from all inventories
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch manager can only transfer from their branch
        if ($user->isBranchManager()) {
            return $user->branch_id === $inventory->branch_id;
        }

        // Sales users cannot transfer stock
        return false;
    }

    /**
     * Check if user can view low stock items
     */
    public function viewLowStock(User $user): bool
    {
        // Admin and Manager can view low stock
        return $user->isSuperAdmin() || $user->isBranchManager();
    }

    /**
     * Check if user can restore inventory
     */
    public function restore(User $user, Inventory $inventory): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Check if user can force delete inventory
     */
    public function forceDelete(User $user, Inventory $inventory): bool
    {
        return $user->isSuperAdmin();
    }
}
