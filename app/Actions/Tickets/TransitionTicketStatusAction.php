<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TicketWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionTicketStatusAction
{
    public function __construct(
        protected TicketWorkflowService $workflowService,
        protected AuditService $auditService
    ) {}

    /**
     * Validate and execute ticket status transition.
     *
     * @throws ValidationException
     */
    public function execute(Ticket $ticket, TicketStatus $newStatus, User $actor, ?string $resolutionNotes = null): Ticket
    {
        // Validate workflow transition rules & role boundaries
        $this->workflowService->validateTransition($ticket, $newStatus, $actor, $resolutionNotes);

        return DB::transaction(function () use ($ticket, $newStatus, $actor, $resolutionNotes) {
            $oldStatus = $ticket->status;
            $updates = [
                'status' => $newStatus,
            ];

            if ($newStatus === TicketStatus::Resolved) {
                $updates['resolution_notes'] = $resolutionNotes;
                $updates['resolved_at'] = now();
            } elseif ($newStatus === TicketStatus::Closed) {
                $updates['closed_at'] = now();
            } elseif ($newStatus === TicketStatus::Open && in_array($oldStatus, [TicketStatus::Resolved, TicketStatus::Closed, TicketStatus::Rejected])) {
                // When reopening, reset resolution timestamps
                $updates['resolved_at'] = null;
                $updates['closed_at'] = null;
            }

            $ticket->update($updates);

            // Audit history
            $this->auditService->logTicketAction(
                ticket: $ticket,
                action: 'status_changed',
                actor: $actor,
                oldValues: ['status' => $oldStatus->value],
                newValues: [
                    'status' => $newStatus->value,
                    'resolution_notes' => $updates['resolution_notes'] ?? null,
                ]
            );

            // Notify ticket creator if someone else changed status
            if ($ticket->creator && $ticket->creator->id !== $actor->id) {
                $ticket->creator->notify(new \App\Notifications\TicketStatusChangedNotification($ticket, $oldStatus, $newStatus, $actor));
            }

            // Notify assigned EDP if someone else (e.g. user confirming resolution or Central EDP) changed status
            if ($ticket->assignedEdp && $ticket->assignedEdp->id !== $actor->id) {
                $ticket->assignedEdp->notify(new \App\Notifications\TicketStatusChangedNotification($ticket, $oldStatus, $newStatus, $actor));
            }

            return $ticket;
        });
    }
}
