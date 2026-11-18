<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public User $assigner,
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
            'title' => "Ticket Assigned: #{$this->ticket->ticket_number}",
            'message' => "You have been assigned to ticket '{$this->ticket->subject}' by {$this->assigner->name}.",
            'url' => route('tickets.show', $this->ticket),
            'type' => 'ticket_assigned',
        ];
    }

    public function toWhatsApp(object $notifiable): string
    {
        $ticket = $this->ticket;
        $branchCode = $ticket->branch->code ?? 'HQ';
        $priorityLabel = strtoupper($ticket->priority->value ?? 'MEDIUM');
        $url = route('tickets.show', $ticket);

        return "📋 *TICKET ASSIGNMENT: [{$ticket->ticket_number}]*\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n"
            . "Halo *{$notifiable->name}*, Anda telah ditugaskan untuk menangani tiket ini oleh *{$this->assigner->name}*.\n\n"
            . "📍 *Cabang:* {$branchCode}\n"
            . "👤 *Pelapor:* {$ticket->creator->name}\n"
            . "⚡ *Prioritas:* {$priorityLabel}\n"
            . "📌 *Subjek:* {$ticket->subject}\n\n"
            . "🔗 *Buka Tiket:*\n{$url}\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n"
            . "_Centralized IT Helpdesk System_";
    }
}
