<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Super Admin Control Center</h4>
            <div class="text-muted small">System-wide infrastructure, master data management, and operational security oversight</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people me-1"></i> Manage Users
            </a>
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-building me-1"></i> Manage Branches
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
                            <div class="metric-value">{{ $metrics['total_users'] }}</div>
                            <div class="metric-label">Active Users</div>
                        </div>
                        <i class="bi bi-people fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['total_branches'] }}</div>
                            <div class="metric-label">Connected Branches</div>
                        </div>
                        <i class="bi bi-buildings fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['total_tickets'] }}</div>
                            <div class="metric-label">Total Tickets Processed</div>
                        </div>
                        <i class="bi bi-ticket-detailed fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $metrics['active_feature_requests'] }}</div>
                            <div class="metric-label">Active Feature Lifecycle</div>
                        </div>
                        <i class="bi bi-kanban fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- System Audit Trail -->
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-check text-success me-2"></i>Recent System Audit Activity</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Ticket #</th>
                                <th>Actor</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAudits as $log)
                                <tr>
                                    <td class="text-muted">{{ $log->created_at->format('d M H:i') }}</td>
                                    <td>
                                        <a href="{{ route('tickets.show', $log->ticket) }}" class="fw-semibold font-monospace text-decoration-none">
                                            {{ $log->ticket->ticket_number }}
                                        </a>
                                    </td>
                                    <td>{{ $log->actor->name ?? 'System' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No audit logs recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- System Status Summary -->
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-pie-chart text-primary me-2"></i>Tickets by Status Distribution
                </div>
                <div class="card-body p-3">
                    @foreach (App\Enums\TicketStatus::cases() as $st)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="{{ $st->badgeClass() }} me-2">&bull;</span>
                                <span class="small fw-semibold">{{ $st->label() }}</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill">
                                {{ $ticketsByStatus[$st->value] ?? 0 }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
