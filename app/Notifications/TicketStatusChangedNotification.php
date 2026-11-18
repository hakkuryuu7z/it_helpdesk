<?php

namespace App\Notifications;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketStatus $fromStatus,
        public TicketStatus $toStatus,
        public User $actor,
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
            'title' => "Status Updated: #{$this->ticket->ticket_number}",
            'message' => "Ticket status changed to '{$this->toStatus->label()}' by {$this->actor->name}.",
            'url' => route('tickets.show', $this->ticket),
            'type' => 'status_changed',
        ];
    }
}
