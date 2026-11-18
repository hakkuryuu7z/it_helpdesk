<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Branch Administrative Portal</h4>
            <div class="text-muted small">Branch: <strong>{{ auth()->user()->branch->name ?? 'HQ' }}</strong> ({{ auth()->user()->branch->code ?? 'HQ' }})</div>
        </div>
        <div>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Branch Ticket
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
                            <div class="metric-value">{{ $metrics['total_branch_tickets'] }}</div>
                            <div class="metric-label">Total Branch Tickets</div>
                        </div>
                        <i class="bi bi-building fs-1 text-primary opacity-25"></i>
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
                            <div class="metric-label">Active / In Progress</div>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['resolved_tickets'] }}</div>
                            <div class="metric-label">Resolved (Awaiting Closure)</div>
                        </div>
                        <i class="bi bi-check-circle fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['closed_tickets'] }}</div>
                            <div class="metric-label">Closed Tickets</div>
                        </div>
                        <i class="bi bi-archive fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Branch Tickets -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Recent Branch Tickets</span>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">View All &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 140px;">Ticket #</th>
                        <th>Subject</th>
                        <th style="width: 140px;">Reporter</th>
                        <th style="width: 110px;">Priority</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 140px;">Assigned EDP</th>
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
                            <td>{{ $ticket->creator->name }}</td>
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
                                    <span class="small">{{ $ticket->assignedEdp->name }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary small">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-outline-secondary btn-sm" title="View Ticket">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No tickets found for this branch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
