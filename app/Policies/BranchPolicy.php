<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::CentralEdp->value,
            UserRole::BranchEdp->value,
            UserRole::BranchAdmin->value,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->assignedBranches()->where('branches.id', $branch->id)->exists();
        }

        return (int) $user->branch_id === $branch->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
