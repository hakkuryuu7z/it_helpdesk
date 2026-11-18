<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return true; // Query scope Ticket::forUser() enforces row-level visibility
    }

    /**
     * Determine whether the user can view the specific ticket.
     * Mandatory Rules:
     * - Regular user: Strictly own tickets.
     * - Branch EDP: Assigned branches only.
     * - Central EDP & Super Admin: All branches.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->canServiceBranch($ticket->branch_id);
        }

        if ($user->hasRole(UserRole::BranchAdmin->value)) {
            return (int) $user->branch_id === (int) $ticket->branch_id;
        }

        // Regular user: strictly own ticket
        return (int) $ticket->created_by === (int) $user->id;
    }

    /**
     * Determine whether the user can create tickets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can assign/reassign tickets.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->canServiceBranch($ticket->branch_id);
        }

        return false;
    }

    /**
     * Determine whether the user can update ticket status.
     */
    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->canServiceBranch($ticket->branch_id);
        }

        if ($user->hasRole(UserRole::RegularUser->value)) {
            // Regular user can confirm resolution or request reopen on own tickets
            return (int) $ticket->created_by === (int) $user->id
                && in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed, TicketStatus::Rejected], true);
        }

        return false;
    }

    /**
     * Determine whether the user can update ticket priority.
     */
    public function updatePriority(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->canServiceBranch($ticket->branch_id);
        }

        return false;
    }

    /**
     * Determine whether the user can post internal notes.
     */
    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::CentralEdp->value,
            UserRole::BranchEdp->value,
        ]) && $this->view($user, $ticket);
    }

    /**
     * Determine whether the user can view internal notes.
     */
    public function viewInternalNotes(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::CentralEdp->value,
            UserRole::BranchEdp->value,
        ]);
    }

    /**
     * Determine whether the user can delete a ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
