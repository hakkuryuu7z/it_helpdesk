<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::CentralEdp->value,
            UserRole::BranchEdp->value,
            UserRole::BranchAdmin->value,
        ]);
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->assignedBranches()->where('branches.id', $department->branch_id)->exists();
        }

        return (int) $user->branch_id === $department->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::BranchAdmin->value]);
    }

    public function update(User $user, Department $department): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchAdmin->value)) {
            return (int) $user->branch_id === $department->branch_id;
        }

        return false;
    }

    public function delete(User $user, Department $department): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchAdmin->value)) {
            return (int) $user->branch_id === $department->branch_id;
        }

        return false;
    }
}
