<?php

namespace App\Actions\Tickets;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddTicketCommentAction
{
    public function __construct(
        protected FileUploadService $fileService,
        protected AuditService $auditService
    ) {}

    /**
     * Post a comment or internal note on a ticket with optional attachment.
     *
     * @throws ValidationException
     */
    public function execute(Ticket $ticket, User $user, string $message, bool $isInternal = false, ?UploadedFile $attachment = null): TicketComment
    {
        // Enforce rule: Regular users can NEVER post internal notes
        if ($isInternal && $user->hasRole(UserRole::RegularUser->value)) {
            throw ValidationException::withMessages([
                'is_internal' => ['Regular users are not authorized to create internal notes.'],
            ]);
        }

        return DB::transaction(function () use ($ticket, $user, $message, $isInternal, $attachment) {
            $comment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $message,
                'is_internal' => $isInternal,
            ]);

            if ($attachment) {
                $this->fileService->uploadAttachment($ticket, $attachment, $user, $comment->id);
            }

            // Audit
            $this->auditService->logTicketAction(
                ticket: $ticket,
                action: $isInternal ? 'internal_note_added' : 'comment_added',
                actor: $user,
                newValues: [
                    'comment_id' => $comment->id,
                    'is_internal' => $isInternal,
                ]
            );

            // Notify stakeholders
            if (!$isInternal) {
                // Public message: notify ticket creator if author is someone else
                if ($ticket->creator && $ticket->creator->id !== $user->id) {
                    $ticket->creator->notify(new \App\Notifications\TicketCommentAddedNotification($ticket, $comment, $user));
                }
            }

            // Notify assigned technician if author is someone else
            if ($ticket->assignedEdp && $ticket->assignedEdp->id !== $user->id) {
                $ticket->assignedEdp->notify(new \App\Notifications\TicketCommentAddedNotification($ticket, $comment, $user));
            }

            return $comment;
        });
    }
}
