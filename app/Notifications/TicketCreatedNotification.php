<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', WhatsAppChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => "New Ticket #{$this->ticket->ticket_number}",
            'message' => "Ticket '{$this->ticket->subject}' reported by {$this->ticket->creator->name} ({$this->ticket->branch->code}).",
            'url' => route('tickets.show', $this->ticket),
            'type' => 'ticket_created',
        ];
    }

    public function toWhatsApp(object $notifiable): string
    {
        $ticket = $this->ticket;
        $branchCode = $ticket->branch->code ?? 'HQ';
        $branchName = $ticket->branch->name ?? 'Head Office';
        $creatorName = $ticket->creator->name ?? 'Employee';
        $priorityLabel = strtoupper($ticket->priority->value ?? 'MEDIUM');
        $typeLabel = $ticket->type->label() ?? 'Incident';
        $url = route('tickets.show', $ticket);

        return "🚨 *NEW IT TICKET: [{$ticket->ticket_number}]*\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n"
            . "📍 *Branch:* {$branchCode} - {$branchName}\n"
            . "👤 *Reporter:* {$creatorName}\n"
            . "🏷️ *Type:* {$typeLabel}\n"
            . "⚡ *Priority:* {$priorityLabel}\n"
            . "📌 *Subject:* {$ticket->subject}\n\n"
            . "📝 *Description:*\n" . Str::limit($ticket->description, 180) . "\n\n"
            . "🔗 *Direct Link:*\n{$url}\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n"
            . "_Centralized IT Helpdesk System_";
    }
}
