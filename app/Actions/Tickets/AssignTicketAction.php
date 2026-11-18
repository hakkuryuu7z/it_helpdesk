<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTicketAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Assign or reassign ticket to an EDP technician and record assignment history.
     *
     * @throws ValidationException
     */
    public function execute(Ticket $ticket, User $assignee, User $actor, ?string $notes = null): Ticket
    {
        // Verify assignee has authority to service this ticket's branch
        if (!$assignee->canServiceBranch($ticket->branch_id)) {
            throw ValidationException::withMessages([
                'assigned_to' => ["The selected EDP personnel '{$assignee->name}' is not assigned to service branch ID {$ticket->branch_id}."],
            ]);
        }

        return DB::transaction(function () use ($ticket, $assignee, $actor, $notes) {
            $previousAssigneeId = $ticket->assigned_to;

            // Update current assignee on ticket
            $ticket->update(['assigned_to' => $assignee->id]);

            // Preserve full assignment history
            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'assigned_to' => $assignee->id,
                'assigned_by' => $actor->id,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            // Audit history
            $this->auditService->logTicketAction(
                ticket: $ticket,
                action: $previousAssigneeId ? 'reassigned' : 'assigned',
                actor: $actor,
                oldValues: ['assigned_to' => $previousAssigneeId],
                newValues: ['assigned_to' => $assignee->id, 'assignee_name' => $assignee->name],
                metadata: ['notes' => $notes]
            );

            // Notify assignee
            if ($assignee->id !== $actor->id) {
                $assignee->notify(new \App\Notifications\TicketAssignedNotification($ticket, $actor));
            }

            return $ticket;
        });
    }
}
