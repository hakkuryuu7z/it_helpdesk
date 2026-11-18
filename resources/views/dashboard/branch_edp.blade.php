<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Branch EDP Technical Workbench</h4>
            <div class="text-muted small">
                Personnel: <strong>{{ auth()->user()->name }}</strong> &bull;
                Assigned Branches:
                @foreach (auth()->user()->assignedBranches as $b)
                    <span class="badge bg-secondary font-monospace">{{ $b->code }}</span>
                @endforeach
            </div>
        </div>
        <div>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i> Full Ticket Queue
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
                            <div class="metric-value">{{ $metrics['assigned_to_me'] }}</div>
                            <div class="metric-label">Assigned To Me</div>
                        </div>
                        <i class="bi bi-person-check fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['unassigned_branch'] }}</div>
                            <div class="metric-label">Unassigned in Queue</div>
                        </div>
                        <i class="bi bi-inbox fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['critical_queue'] }}</div>
                            <div class="metric-label">Critical Incidents</div>
                        </div>
                        <i class="bi bi-lightning-charge fs-1 text-danger opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['total_branch_tickets'] }}</div>
                            <div class="metric-label">Total Branch Tickets</div>
                        </div>
                        <i class="bi bi-building fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- My Assigned Active Tickets -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-workspace text-primary me-2"></i>My Active Workload ({{ $myAssignedTickets->count() }})</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($myAssignedTickets as $t)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <a href="{{ route('tickets.show', $t) }}" class="fw-bold font-monospace text-decoration-none">
                                    {{ $t->ticket_number }}
                                </a>
                                <div>
                                    <span class="badge {{ $t->priority->badgeClass() }} small">{{ $t->priority->label() }}</span>
                                    <span class="badge {{ $t->status->badgeClass() }} small">{{ $t->status->label() }}</span>
                                </div>
                            </div>
                            <div class="fw-semibold text-dark small">{{ $t->subject }}</div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                <span>{{ $t->branch->code }} &bull; {{ $t->creator->name }}</span>
                                <a href="{{ route('tickets.show', $t) }}" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                    Handle &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            No active tickets assigned directly to you right now.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Unassigned Branch Queue -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hourglass-split text-warning me-2"></i>Unassigned Queue ({{ $unassignedTickets->count() }})</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($unassignedTickets as $t)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <a href="{{ route('tickets.show', $t) }}" class="fw-bold font-monospace text-decoration-none">
                                    {{ $t->ticket_number }}
                                </a>
                                <div>
                                    <span class="badge {{ $t->priority->badgeClass() }} small">{{ $t->priority->label() }}</span>
                                    <span class="badge {{ $t->status->badgeClass() }} small">{{ $t->status->label() }}</span>
                                </div>
                            </div>
                            <div class="fw-semibold text-dark small">{{ $t->subject }}</div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                <span>{{ $t->branch->code }} &bull; {{ $t->created_at->diffForHumans() }}</span>
                                <a href="{{ route('tickets.show', $t) }}" class="btn btn-xs btn-outline-warning py-0 px-2 text-dark" style="font-size: 0.75rem;">
                                    Assign / Claim
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            All tickets in your assigned branches have been claimed.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
