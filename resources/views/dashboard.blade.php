<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Helpdesk Operations Dashboard</h4>
            <div class="text-muted small">Centralized incident monitoring and ticket overview</div>
        </div>
        <div>
            <a href="{{ Route::has('tickets.create') ? route('tickets.create') : '#' }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Submit New Ticket
            </a>
        </div>
    </x-slot>

    <!-- Operational Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-widget">
                <div>
                    <div class="stat-title">Active Tickets</div>
                    <div class="stat-value">0</div>
                    <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i>Open & In Progress</div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-ticket-detailed"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-widget">
                <div>
                    <div class="stat-title">Critical / High</div>
                    <div class="stat-value text-danger">0</div>
                    <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>Needs Immediate Action</div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-fire"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-widget">
                <div>
                    <div class="stat-title">Waiting User</div>
                    <div class="stat-value text-warning">0</div>
                    <div class="small text-muted mt-1"><i class="bi bi-person-exclamation me-1"></i>Awaiting confirmation</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-widget">
                <div>
                    <div class="stat-title">Resolved / Closed</div>
                    <div class="stat-value text-success">0</div>
                    <div class="small text-success mt-1"><i class="bi bi-check2-circle me-1"></i>Successfully closed</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-patch-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation & Recent Tickets Card -->
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent Ticket Activity</span>
                    <a href="{{ Route::has('tickets.index') ? route('tickets.index') : '#' }}" class="small text-decoration-none">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject</th>
                                    <th>Branch</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                        No recent tickets found. Master data and ticket workflow will populate this view.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    System Information
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Environment</span>
                            <span class="badge bg-secondary">{{ config('app.env') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Database Engine</span>
                            <span class="fw-semibold">PostgreSQL 16</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Active Timezone</span>
                            <span class="fw-semibold">{{ config('app.timezone') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Current Role</span>
                            <span class="badge bg-primary">{{ Auth::user()->getRoleNames()->first() ?? 'Regular User' }}</span>
                        </li>
                    </ul>

                    <div class="mt-4 p-3 bg-light rounded border small">
                        <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Centralized Architecture</div>
                        <div class="text-muted">
                            All branches submit complaints and requests into this unified system. Branch-level isolation is enforced strictly at the database layer.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
