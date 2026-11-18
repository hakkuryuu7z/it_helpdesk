<?php

namespace App\Actions\Tickets;

use App\Enums\TicketImpact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FileUploadService;
use App\Services\TicketNumberService;
use App\Services\TicketWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateTicketAction
{
    public function __construct(
        protected TicketNumberService $numberService,
        protected TicketWorkflowService $workflowService,
        protected FileUploadService $fileService,
        protected AuditService $auditService,
    ) {}

    /**
     * Execute ticket creation with concurrency-safe numbering and server-side stamps.
     */
    public function execute(array $data, User $creator, ?UploadedFile $attachment = null): Ticket
    {
        return DB::transaction(function () use ($data, $creator, $attachment) {
            // Mandatory Rule 8: branch_id must be assigned server-side using the authenticated user's branch
            // Only Super Admin or Central EDP creating on behalf of a branch may override branch_id
            $branchId = $creator->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value]) && !empty($data['branch_id'])
                ? (int) $data['branch_id']
                : (int) $creator->branch_id;

            // Generate unique ticket number atomically
            $ticketNumber = $this->numberService->generateNextTicketNumber();

            // Calculate priority or enforce regular user constraints
            $impact = is_string($data['impact']) ? TicketImpact::from($data['impact']) : $data['impact'];
            $urgency = is_string($data['urgency']) ? TicketUrgency::from($data['urgency']) : $data['urgency'];
            $type = is_string($data['type']) ? TicketType::from($data['type']) : $data['type'];

            $suggestedPriority = $this->workflowService->calculateSuggestedPriority($impact, $urgency);

            // Regular users cannot force Critical priority directly
            $priority = $suggestedPriority;
            if ($creator->hasRole(UserRole::RegularUser->value) && $priority === TicketPriority::Critical) {
                $priority = TicketPriority::High; // downgraded for initial review
            }

            // Create ticket
            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'branch_id' => $branchId,
                'created_by' => $creator->id,
                'category_id' => $data['category_id'],
                'type' => $type,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $priority,
                'impact' => $impact,
                'urgency' => $urgency,
                'status' => TicketStatus::Open,
            ]);

            // Handle file attachment if uploaded
            if ($attachment) {
                $this->fileService->uploadAttachment($ticket, $attachment, $creator);
            }

            // Audit history
            $this->auditService->logTicketAction(
                ticket: $ticket,
                action: 'created',
                actor: $creator,
                newValues: [
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'type' => $ticket->type->value,
                    'subject' => $ticket->subject,
                ]
            );

            return $ticket;
        });
    }
}
