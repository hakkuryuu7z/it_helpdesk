<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FeatureRequest;
use App\Models\User;

class FeatureRequestPolicy
{
    /**
     * Determine whether the user can view any feature requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the specific feature request.
     */
    public function view(User $user, FeatureRequest $featureRequest): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // Delegate to parent ticket policy
        return $user->can('view', $featureRequest->ticket);
    }

    /**
     * Determine whether the user can update the stage of the feature request.
     * Central EDP and Super Admin have primary authority for development lifecycle.
     */
    public function updateStage(User $user, FeatureRequest $featureRequest): bool
    {
        if (!$user->is_active) {
            return false;
        }

        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value]);
    }

    /**
     * Determine whether the user can edit technical notes, effort, or dev priority.
     */
    public function update(User $user, FeatureRequest $featureRequest): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->canServiceBranch($featureRequest->ticket->branch_id);
        }

        return false;
    }
}
