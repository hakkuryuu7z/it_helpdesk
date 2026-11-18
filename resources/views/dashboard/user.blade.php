<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Employee Service Portal</h4>
            <div class="text-muted small">Welcome, {{ auth()->user()->name }} &bull; Branch: {{ auth()->user()->branch->name ?? 'HQ' }}</div>
        </div>
        <div>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-plus-circle me-1"></i> New Support Ticket
            </a>
        </div>
    </x-slot>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['total_submitted'] }}</div>
                            <div class="metric-label">Total Submitted</div>
                        </div>
                        <i class="bi bi-ticket-perforated fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['open_tickets'] }}</div>
                            <div class="metric-label">In Progress / Open</div>
                        </div>
                        <i class="bi bi-clock-history fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['waiting_user'] }}</div>
                            <div class="metric-label">Waiting Your Response</div>
                        </div>
                        <i class="bi bi-chat-quote fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['resolved'] }}</div>
                            <div class="metric-label">Resolved Tickets</div>
                        </div>
                        <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <a href="{{ route('tickets.create') }}?type=incident" class="card card-body text-decoration-none border-primary h-100 hover-shadow">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-danger-subtle text-danger rounded">
                        <i class="bi bi-exclamation-triangle fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Report Incident</h6>
                        <small class="text-muted">Network, printer, hardware, or system errors</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="{{ route('tickets.create') }}?type=service_request" class="card card-body text-decoration-none border-primary h-100 hover-shadow">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary-subtle text-primary rounded">
                        <i class="bi bi-gear fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Service Request</h6>
                        <small class="text-muted">New accounts, app access, setup requests</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="{{ route('tickets.create') }}?type=feature_request" class="card card-body text-decoration-none border-primary h-100 hover-shadow">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-info-subtle text-info rounded">
                        <i class="bi bi-lightbulb fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Propose Feature</h6>
                        <small class="text-muted">Software enhancements and new reporting tools</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- My Recent Tickets -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>My Recent Tickets</span>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                View All Tickets &rarr;
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 140px;">Ticket #</th>
                        <th>Subject</th>
                        <th style="width: 130px;">Type</th>
                        <th style="width: 110px;">Priority</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 140px;">Assigned EDP</th>
                        <th style="width: 130px;">Created</th>
                        <th style="width: 80px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="fw-semibold text-primary font-monospace text-decoration-none">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $ticket->subject }}</div>
                                <small class="text-muted">{{ $ticket->category->name ?? 'General' }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $ticket->type->value === 'incident' ? 'badge-type-incident' : ($ticket->type->value === 'feature_request' ? 'badge-type-feature_request' : 'badge-type-service_request') }}">
                                    {{ $ticket->type->label() }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $ticket->priority->badgeClass() }}">
                                    {{ $ticket->priority->label() }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $ticket->status->badgeClass() }}">
                                    {{ $ticket->status->label() }}
                                </span>
                            </td>
                            <td>
                                @if ($ticket->assignedEdp)
                                    <span class="small text-dark">{{ $ticket->assignedEdp->name }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary small">Unassigned</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $ticket->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-outline-secondary btn-sm" title="View Ticket">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                You haven't created any support tickets yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
