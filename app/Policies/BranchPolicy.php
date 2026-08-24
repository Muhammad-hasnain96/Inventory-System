<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;

class BranchPolicy
{
    /**
     * Determine if user can view branches
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view branches
        return true;
    }

    /**
     * Determine if user can view a branch
     */
    public function view(User $user, Branch $branch): bool
    {
        // Super admin can view all branches
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch managers can view their own branch
        return $user->branch_id === $branch->id;
    }

    /**
     * Determine if user can create branches
     */
    public function create(User $user): bool
    {
        // Only super admin can create branches
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can update branches
     */
    public function update(User $user, Branch $branch): bool
    {
        // Only super admin can update branches
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can delete branches
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Only super admin can delete branches
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can restore branches
     */
    public function restore(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if user can permanently delete branches
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }
}
