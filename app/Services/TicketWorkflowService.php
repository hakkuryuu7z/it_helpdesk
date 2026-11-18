<?php

namespace App\Services;

use App\Enums\TicketImpact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    /**
     * Map of valid forward and backward status transitions.
     */
    protected array $allowedTransitions = [
        TicketStatus::Open->value => [
            TicketStatus::UnderReview->value,
            TicketStatus::InProgress->value,
            TicketStatus::Rejected->value,
        ],
        TicketStatus::UnderReview->value => [
            TicketStatus::InProgress->value,
            TicketStatus::WaitingUser->value,
            TicketStatus::Rejected->value,
        ],
        TicketStatus::InProgress->value => [
            TicketStatus::WaitingUser->value,
            TicketStatus::Resolved->value,
            TicketStatus::Closed->value,
        ],
        TicketStatus::WaitingUser->value => [
            TicketStatus::InProgress->value,
            TicketStatus::Resolved->value,
        ],
        TicketStatus::Resolved->value => [
            TicketStatus::Closed->value,
            TicketStatus::Open->value, // Reopen
        ],
        TicketStatus::Closed->value => [
            TicketStatus::Open->value, // Reopen
        ],
        TicketStatus::Rejected->value => [
            TicketStatus::Open->value, // Reopen
        ],
    ];

    /**
     * Verify whether a status transition is allowed.
     */
    public function canTransition(Ticket $ticket, TicketStatus $newStatus, User $user): bool
    {
        $current = $ticket->status->value;
        $target = $newStatus->value;

        // Transitioning to same status is invalid
        if ($current === $target) {
            return false;
        }

        // Check if graph allows transition
        if (!in_array($target, $this->allowedTransitions[$current] ?? [], true)) {
            return false;
        }

        // Role-based restrictions:
        // Regular users can only confirm resolution (transition from Resolved -> Closed)
        // or Reopen (Resolved/Closed -> Open) on their own tickets.
        if ($user->hasRole(UserRole::RegularUser->value)) {
            if ($ticket->created_by !== $user->id) {
                return false;
            }

            return in_array($target, [TicketStatus::Closed->value, TicketStatus::Open->value], true);
        }

        // Branch EDP must be assigned to the ticket's branch
        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $user->canServiceBranch($ticket->branch_id);
        }

        // Central EDP & SuperAdmin can execute any valid transition in the workflow graph
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value]);
    }

    /**
     * Get all allowed next statuses for a ticket and user.
     *
     * @return array<TicketStatus>
     */
    public function getAllowedNextStatuses(Ticket $ticket, User $user): array
    {
        $current = $ticket->status->value;
        $targets = $this->allowedTransitions[$current] ?? [];

        $validStatuses = [];
        foreach ($targets as $targetValue) {
            $statusEnum = TicketStatus::tryFrom($targetValue);
            if ($statusEnum && $this->canTransition($ticket, $statusEnum, $user)) {
                $validStatuses[] = $statusEnum;
            }
        }

        return $validStatuses;
    }

    /**
     * Get raw transitions map.
     */
    public function getAllowedTransitions(): array
    {
        return $this->allowedTransitions;
    }

    /**
     * Validate status transition and throw validation exception if invalid.
     *
     * @throws ValidationException
     */
    public function validateTransition(Ticket $ticket, TicketStatus $newStatus, User $user, ?string $resolutionNotes = null): void
    {
        if (!$this->canTransition($ticket, $newStatus, $user)) {
            $reason = match (true) {
                $ticket->status === TicketStatus::Open && $newStatus === TicketStatus::Closed => 
                    "Tiket berstatus 'Open' tidak dapat langsung diubah ke 'Closed'. Tiket harus diproses terlebih dahulu melalui 'In Progress' / 'Under Review', kemudian diselesaikan ('Resolved') dengan mengisi solusi perbaikan sebelum dapat ditutup ('Closed'). Jika tiket tidak valid atau dibatalkan, silakan ubah status ke 'Rejected'.",
                $newStatus === TicketStatus::Resolved && empty(trim($resolutionNotes ?? '')) =>
                    "Status 'Resolved' mewajibkan pengisian catatan solusi perbaikan (Resolution Notes).",
                default => 
                    "Alur transisi status dari '{$ticket->status->label()}' ke '{$newStatus->label()}' tidak diizinkan dalam sistem helpdesk.",
            };

            throw ValidationException::withMessages([
                'status' => ["Status transition from '{$ticket->status->label()}' to '{$newStatus->label()}' is not permitted. {$reason}"],
            ]);
        }

        // Mandatory rule: EDP must provide resolution notes before resolving a ticket
        if ($newStatus === TicketStatus::Resolved && empty(trim($resolutionNotes ?? ''))) {
            throw ValidationException::withMessages([
                'resolution_notes' => ['Resolution notes are mandatory before marking a ticket as Resolved.'],
            ]);
        }
    }

    /**
     * Calculate suggested priority based on Impact and Urgency matrix.
     */
    public function calculateSuggestedPriority(TicketImpact $impact, TicketUrgency $urgency): TicketPriority
    {
        if ($impact === TicketImpact::High && $urgency === TicketUrgency::High) {
            return TicketPriority::Critical;
        }

        if ($impact === TicketImpact::High || $urgency === TicketUrgency::High) {
            return TicketPriority::High;
        }

        if ($impact === TicketImpact::Medium || $urgency === TicketUrgency::Medium) {
            return TicketPriority::Medium;
        }

        return TicketPriority::Low;
    }
}
