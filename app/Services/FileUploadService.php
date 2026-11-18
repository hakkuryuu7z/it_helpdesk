<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileUploadService
{
    protected array $disallowedExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'sh', 'vbs', 'com', 'msi', 'bin', 'jar'
    ];

    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-zip-compressed',
    ];

    /**
     * Upload an attachment securely to private storage and record it.
     *
     * @throws ValidationException
     */
    public function uploadAttachment(Ticket $ticket, UploadedFile $file, User $user, ?int $commentId = null): TicketAttachment
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        // 1. Blacklist extension check
        if (in_array($extension, $this->disallowedExtensions, true)) {
            throw ValidationException::withMessages([
                'attachment' => ["Files with extension .{$extension} are forbidden for security reasons."],
            ]);
        }

        // 2. Allowed MIME types check
        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw ValidationException::withMessages([
                'attachment' => ['The uploaded file type is not supported. Permitted types: PDF, Images, Word, Excel, CSV, ZIP.'],
            ]);
        }

        // 3. Max size check (10MB = 10485760 bytes)
        if ($file->getSize() > 10485760) {
            throw ValidationException::withMessages([
                'attachment' => ['File size exceeds maximum allowed limit of 10 MB.'],
            ]);
        }

        // 4. Safe random storage name
        $safeFileName = Str::uuid()->toString() . '.' . $extension;
        $directory = "private/attachments/{$ticket->id}";

        $storedPath = $file->storeAs($directory, $safeFileName, 'local');

        return TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'comment_id' => $commentId,
            'uploaded_by' => $user->id,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
        ]);
    }
}
