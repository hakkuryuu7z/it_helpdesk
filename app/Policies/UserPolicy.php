<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::CentralEdp->value,
            UserRole::BranchAdmin->value,
        ]);
    }

    public function view(User $user, User $target): bool
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchAdmin->value)) {
            return (int) $user->branch_id === (int) $target->branch_id;
        }

        return $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::BranchAdmin->value,
        ]);
    }

    public function update(User $user, User $target): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchAdmin->value)) {
            // Branch admin can only update non-admin users within their own branch
            return (int) $user->branch_id === (int) $target->branch_id
                && !$target->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value]);
        }

        return $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        // Only SuperAdmin can delete users, and cannot delete themselves
        return $user->hasRole(UserRole::SuperAdmin->value) && $user->id !== $target->id;
    }
}
