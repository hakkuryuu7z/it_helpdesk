<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;

class AuditService
{
    /**
     * Record an immutable audit log entry for a ticket operation.
     */
    public function logTicketAction(
        Ticket $ticket,
        string $action,
        ?User $actor = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): TicketHistory {
        return TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
