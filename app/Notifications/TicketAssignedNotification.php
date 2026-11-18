<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
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
        return ['database'];
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
}
