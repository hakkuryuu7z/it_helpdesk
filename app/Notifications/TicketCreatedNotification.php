<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
