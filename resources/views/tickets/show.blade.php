<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0 font-monospace text-primary">{{ $ticket->ticket_number }}</h4>
                <span class="badge {{ $ticket->type->value === 'incident' ? 'badge-type-incident' : ($ticket->type->value === 'feature_request' ? 'badge-type-feature_request' : 'badge-type-service_request') }}">
                    {{ $ticket->type->label() }}
                </span>
                <span class="badge {{ $ticket->status->badgeClass() }}">
                    {{ $ticket->status->label() }}
                </span>
                <span class="badge {{ $ticket->priority->badgeClass() }}">
                    {{ $ticket->priority->label() }}
                </span>
            </div>
            <div class="text-muted small">
                Reported by <span class="fw-semibold text-dark">{{ $ticket->creator->name }}</span>
                &bull; Branch: <span class="badge bg-secondary font-monospace">{{ $ticket->branch->code }}</span> {{ $ticket->branch->name }}
                &bull; {{ $ticket->created_at->format('d M Y, H:i') }}
            </div>
        </div>
        <div>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Tickets
            </a>
        </div>
    </x-slot>

    <div class="row g-4">
        <!-- Main Column (Left, 8 cols) -->
        <div class="col-12 col-lg-8">
            <!-- Ticket Description Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-6">{{ $ticket->subject }}</span>
                    <span class="badge bg-light text-dark border">{{ $ticket->category->name ?? 'General' }}</span>
                </div>
                <div class="card-body p-4">
                    <div class="text-secondary" style="white-space: pre-line; line-height: 1.6;">{{ $ticket->description }}</div>

                    <!-- Direct Attachments -->
                    @if ($ticket->attachments->whereNull('comment_id')->count() > 0)
                        <div class="mt-4 pt-3 border-top">
                            <div class="small fw-semibold text-muted mb-2"><i class="bi bi-paperclip me-1"></i> Attachments</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($ticket->attachments->whereNull('comment_id') as $att)
                                    <a href="{{ route('attachments.download', $att) }}" class="btn btn-sm btn-light border d-flex align-items-center gap-2 text-decoration-none">
                                        <i class="bi {{ $att->isImage() ? 'bi-file-earmark-image' : 'bi-file-earmark-text' }} text-primary"></i>
                                        <span class="small text-truncate" style="max-width: 200px;">{{ $att->original_name }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary small">{{ $att->formatted_size }}</span>
                                        <i class="bi bi-download small text-muted"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Resolution Notes if Resolved -->
                    @if ($ticket->resolution_notes)
                        <div class="mt-4 p-3 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                            <div class="d-flex align-items-center gap-2 text-success fw-bold small mb-1">
                                <i class="bi bi-check-circle-fill"></i> Resolution Details
                                @if ($ticket->resolved_at)
                                    <span class="text-muted fw-normal" style="font-size: 0.75rem;">(Resolved {{ $ticket->resolved_at->format('d M Y, H:i') }})</span>
                                @endif
                            </div>
                            <div class="small text-dark" style="white-space: pre-line;">{{ $ticket->resolution_notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Discussion & Comments -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Ticket Discussion & Notes</span>
                    <span class="badge bg-secondary rounded-pill">{{ $comments->count() }}</span>
                </div>
                <div class="card-body p-4">
                    <!-- Comments Stream -->
                    <div class="d-flex flex-column gap-3 mb-4">
                        @forelse ($comments as $comment)
                            <div class="p-3 rounded border {{ $comment->is_internal ? 'ticket-internal-note' : 'bg-light' }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center"
                                             style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold small text-dark">{{ $comment->user->name }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary small" style="font-size: 0.7rem;">
                                            {{ ucwords(str_replace('_', ' ', $comment->user->roles->first()?->name ?? 'User')) }}
                                        </span>
                                        @if ($comment->is_internal)
                                            <span class="badge bg-warning text-dark small" style="font-size: 0.7rem;">
                                                <i class="bi bi-lock-fill me-1"></i>Internal EDP Note
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        {{ $comment->created_at->format('d M Y, H:i') }}
                                    </div>
                                </div>
                                <div class="small text-secondary" style="white-space: pre-line; line-height: 1.5;">{{ $comment->message }}</div>

                                @if ($comment->attachments->count() > 0)
                                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-10 d-flex flex-wrap gap-2">
                                        @foreach ($comment->attachments as $att)
                                            <a href="{{ route('attachments.download', $att) }}" class="btn btn-xs btn-white border py-0 px-2 small d-flex align-items-center gap-1 text-decoration-none">
                                                <i class="bi bi-paperclip text-muted"></i>
                                                <span>{{ $att->original_name }}</span>
                                                <span class="text-muted" style="font-size: 0.7rem;">({{ $att->formatted_size }})</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-chat-left-dots fs-3 d-block mb-1 text-secondary opacity-50"></i>
                                No comments posted yet.
                            </div>
                        @endforelse
                    </div>

                    <!-- Add Comment Form -->
                    @if (!$ticket->isClosed())
                        <div class="pt-3 border-top">
                            <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-2">
                                    <label for="comment_message" class="form-label small fw-semibold">Add Response / Message</label>
                                    <textarea name="message" id="comment_message" rows="3" required
                                              class="form-control form-control-sm @error('message') is-invalid @enderror"
                                              placeholder="Write your update or reply..."></textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row align-items-center g-2">
                                    <div class="col-12 col-md-6">
                                        <input type="file" name="attachment" class="form-control form-control-sm" title="Upload attachment">
                                    </div>

                                    <div class="col-12 col-md-6 text-md-end">
                                        @can('addInternalNote', $ticket)
                                            <div class="form-check form-check-inline me-3">
                                                <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="is_internal_check">
                                                <label class="form-check-label small text-warning-emphasis fw-semibold" for="is_internal_check">
                                                    <i class="bi bi-lock-fill me-1"></i>Internal EDP Note
                                                </label>
                                            </div>
                                        @endcan

                                        <button type="submit" class="btn btn-primary btn-sm px-3">
                                            <i class="bi bi-send me-1"></i> Post Reply
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-secondary small mb-0 text-center py-2">
                            <i class="bi bi-lock-fill me-1"></i> This ticket is closed. Reopen the ticket to continue discussion.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Ticket Audit History -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Audit Trail & Activity Log</span>
                    <span class="badge bg-secondary rounded-pill">{{ $ticket->histories->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        @foreach ($ticket->histories as $h)
                            <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                                <div>
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace me-1 text-uppercase" style="font-size: 0.675rem;">
                                        {{ str_replace('_', ' ', $h->action) }}
                                    </span>
                                    <span class="fw-semibold">{{ $h->user->name ?? 'System' }}</span>
                                    @if ($h->new_values)
                                        <span class="text-muted ms-1">
                                            @if (isset($h->new_values['status']))
                                                &rarr; Status: <strong>{{ $h->new_values['status'] }}</strong>
                                            @elseif (isset($h->new_values['assignee_name']))
                                                &rarr; Assigned to <strong>{{ $h->new_values['assignee_name'] }}</strong>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                                <span class="text-muted" style="font-size: 0.725rem;">{{ $h->created_at->format('d M, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column (Sidebar, 4 cols) -->
        <div class="col-12 col-lg-4">
            <!-- Status Workflow Actions Card -->
            <div class="card mb-4">
                <div class="card-header">
                    Workflow Actions
                </div>
                <div class="card-body">
                    <!-- Regular User Confirmation Actions -->
                    @if (auth()->user()->hasRole(App\Enums\UserRole::RegularUser->value) && $ticket->isResolved())
                        <div class="p-3 bg-light rounded border mb-3 text-center">
                            <div class="fw-bold small mb-1">Has your problem been solved?</div>
                            <div class="small text-muted mb-3">Please confirm if the EDP resolution resolved your issue.</div>
                            <div class="d-grid gap-2">
                                <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ App\Enums\TicketStatus::Closed->value }}">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="bi bi-check2-circle me-1"></i> Yes, Confirm & Close Ticket
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ App\Enums\TicketStatus::Open->value }}">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Reopen this ticket?');">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> No, Reopen Ticket
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- EDP / Admin Workflow Transition Form -->
                    @can('updateStatus', $ticket)
                        @if (!auth()->user()->hasRole(App\Enums\UserRole::RegularUser->value))
                            <form method="POST" action="{{ route('tickets.status', $ticket) }}" x-data="{ targetStatus: '{{ $ticket->status->value }}' }">
                                @csrf
                                @method('PATCH')

                                <div class="mb-3">
                                    <label for="status_select" class="form-label small fw-semibold">Update Status</label>
                                    <select name="status" id="status_select" x-model="targetStatus" class="form-select form-select-sm">
                                        @foreach (App\Enums\TicketStatus::cases() as $st)
                                            <option value="{{ $st->value }}" {{ $ticket->status === $st ? 'selected' : '' }}>
                                                {{ $st->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Mandatory Resolution Notes on Resolved -->
                                <div class="mb-3" x-show="targetStatus === 'resolved'" x-cloak>
                                    <label for="resolution_notes" class="form-label small fw-semibold text-success">
                                        <i class="bi bi-check-circle me-1"></i> Resolution Notes <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="resolution_notes" id="resolution_notes" rows="3"
                                              class="form-control form-control-sm"
                                              placeholder="Explain the technical solution, steps taken, or replacement parts...">{{ old('resolution_notes', $ticket->resolution_notes) }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-arrow-repeat me-1"></i> Apply Status Transition
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>

            <!-- EDP Assignment Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>EDP Assignment</span>
                    @if ($ticket->assignedEdp)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Assigned</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unassigned</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($ticket->assignedEdp)
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center"
                                 style="width: 38px; height: 38px; font-size: 0.9rem;">
                                {{ strtoupper(substr($ticket->assignedEdp->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold text-dark small">{{ $ticket->assignedEdp->name }}</div>
                                <div class="text-muted" style="font-size: 0.725rem;">{{ $ticket->assignedEdp->email }}</div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small mb-3">No EDP personnel assigned to this ticket yet.</div>
                    @endif

                    @can('assign', $ticket)
                        <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="border-top pt-3">
                            @csrf
                            <div class="mb-2">
                                <label for="assigned_to" class="form-label small fw-semibold">Assign Technician</label>
                                <select name="assigned_to" id="assigned_to" required class="form-select form-select-sm">
                                    <option value="">Select Eligible EDP</option>
                                    @foreach ($eligibleAssignees as $edp)
                                        <option value="{{ $edp->id }}" {{ $ticket->assigned_to === $edp->id ? 'selected' : '' }}>
                                            {{ $edp->name }} ({{ $edp->roles->first()?->name === 'central_edp' ? 'Central' : 'Branch' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional assignment note...">
                            </div>

                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-person-check me-1"></i> Update Assignee
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <!-- Metadata Card -->
            <div class="card">
                <div class="card-header">
                    Ticket Details
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-3">
                            <span class="text-muted">Originating Branch</span>
                            <span class="fw-semibold">{{ $ticket->branch->name }} ({{ $ticket->branch->code }})</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-3">
                            <span class="text-muted">Creator Department</span>
                            <span class="fw-semibold">{{ $ticket->creator->department->name ?? 'General' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-3">
                            <span class="text-muted">Impact</span>
                            <span>{{ $ticket->impact->label() }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-3">
                            <span class="text-muted">Urgency</span>
                            <span>{{ $ticket->urgency->label() }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-3">
                            <span class="text-muted">Created At</span>
                            <span>{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                        </li>
                        @if ($ticket->resolved_at)
                            <li class="list-group-item d-flex justify-content-between px-3">
                                <span class="text-muted">Resolved At</span>
                                <span class="text-success">{{ $ticket->resolved_at->format('d M Y, H:i') }}</span>
                            </li>
                        @endif
                        @if ($ticket->closed_at)
                            <li class="list-group-item d-flex justify-content-between px-3">
                                <span class="text-muted">Closed At</span>
                                <span>{{ $ticket->closed_at->format('d M Y, H:i') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
