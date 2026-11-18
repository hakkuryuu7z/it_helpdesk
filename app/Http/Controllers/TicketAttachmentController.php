<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    /**
     * Download or stream ticket attachment with strict authorization.
     */
    public function download(TicketAttachment $attachment, Request $request): StreamedResponse
    {
        $ticket = $attachment->ticket;
        $user = $request->user();

        // 1. Authorize ticket access
        $this->authorize('view', $ticket);

        // 2. If attached to an internal note, verify internal note access
        if ($attachment->comment && $attachment->comment->is_internal) {
            $this->authorize('viewInternalNotes', $ticket);
        }

        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found on storage.');
        }

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
            ]
        );
    }
}
