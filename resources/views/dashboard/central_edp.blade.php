<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Central EDP Operations Center</h4>
            <div class="text-muted small">Cross-branch monitoring, incident triage, and feature engineering oversight</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('feature-requests.index') }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-lightbulb me-1"></i> Feature Pipeline
            </a>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i> All Tickets
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
                            <div class="metric-value">{{ $metrics['total_system_tickets'] }}</div>
                            <div class="metric-label">System-Wide Tickets</div>
                        </div>
                        <i class="bi bi-hdd-network fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['unassigned_tickets'] }}</div>
                            <div class="metric-label">Unassigned Pool</div>
                        </div>
                        <i class="bi bi-inboxes fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['critical_incidents'] }}</div>
                            <div class="metric-label">Critical Incidents</div>
                        </div>
                        <i class="bi bi-exclamation-octagon fs-1 text-danger opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['feature_requests_active'] }}</div>
                            <div class="metric-label">Active Feature Lifecycle</div>
                        </div>
                        <i class="bi bi-kanban fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Unassigned Escalation Queue -->
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-exclamation-triangle text-warning me-2"></i>Triage & Unassigned Queue ({{ $unassignedQueue->count() }})</span>
                    <a href="{{ route('tickets.index') }}?status=open" class="btn btn-xs btn-outline-secondary py-0">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Branch</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unassignedQueue as $t)
                                <tr>
                                    <td>
                                        <a href="{{ route('tickets.show', $t) }}" class="fw-bold font-monospace text-decoration-none">
                                            {{ $t->ticket_number }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-secondary font-monospace">{{ $t->branch->code }}</span></td>
                                    <td>
                                        <div class="text-truncate fw-semibold" style="max-width: 250px;">{{ $t->subject }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $t->priority->badgeClass() }}">{{ $t->priority->label() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('tickets.show', $t) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                            Assign
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">All incoming tickets are assigned to technicians.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Branch Volume Breakdown -->
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-buildings text-primary me-2"></i>Ticket Volume by Branch
                </div>
                <div class="card-body p-3">
                    @forelse ($ticketsByBranch as $branch)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="badge bg-light text-dark border font-monospace me-2">{{ $branch->code }}</span>
                                <span class="small fw-semibold">{{ $branch->name }}</span>
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $branch->tickets_count }} tickets</span>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted small">No branch statistics available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Active Feature Requests Pipeline -->
    <div class="card">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-lightbulb text-info me-2"></i>Active Feature Requests Development Pipeline</span>
            <a href="{{ route('feature-requests.index') }}" class="btn btn-outline-secondary btn-sm">Full Pipeline &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th style="width: 140px;">Ticket #</th>
                        <th>Feature Title</th>
                        <th style="width: 120px;">Branch</th>
                        <th style="width: 120px;">Dev Priority</th>
                        <th style="width: 140px;">Stage</th>
                        <th style="width: 80px;" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activeFeatureRequests as $fr)
                        <tr>
                            <td>
                                <a href="{{ route('feature-requests.show', $fr) }}" class="fw-bold font-monospace text-decoration-none">
                                    {{ $fr->ticket->ticket_number }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $fr->ticket->subject }}</div>
                            </td>
                            <td><span class="badge bg-secondary font-monospace">{{ $fr->ticket->branch->code }}</span></td>
                            <td>
                                @if ($fr->dev_priority)
                                    <span class="badge bg-{{ match($fr->dev_priority) { 'critical' => 'danger', 'high' => 'warning text-dark', 'medium' => 'info text-dark', default => 'secondary' } }}">
                                        {{ ucfirst($fr->dev_priority) }}
                                    </span>
                                @else
                                    <span class="text-muted small">TBD</span>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $fr->stage->badgeClass() }}">
                                    {{ $fr->stage->label() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('feature-requests.show', $fr) }}" class="btn btn-sm btn-outline-info py-0 px-2">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No active feature requests in pipeline.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
