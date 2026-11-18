<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketCommentAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketComment $comment,
        public User $author,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $internalText = $this->comment->is_internal ? ' (Internal Note)' : '';
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => "New Message{$internalText} on #{$this->ticket->ticket_number}",
            'message' => "{$this->author->name}: " . \Illuminate\Support\Str::limit($this->comment->comment, 80),
            'url' => route('tickets.show', $this->ticket),
            'type' => 'comment_added',
        ];
    }
}
