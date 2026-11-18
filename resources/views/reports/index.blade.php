<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Analytics & Reporting</h4>
            <div class="text-muted small">Multi-branch operational metrics, resolution timelines, and secure data export</div>
        </div>
        <div>
            <a href="{{ route('reports.export-csv', request()->query()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
            </a>
        </div>
    </x-slot>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $totalCount }}</div>
                            <div class="metric-label">Total Tickets</div>
                        </div>
                        <i class="bi bi-ticket-perforated fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $resolvedCount }}</div>
                            <div class="metric-label">Resolved & Closed</div>
                        </div>
                        <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">
                                {{ $totalCount > 0 ? round(($resolvedCount / $totalCount) * 100, 1) : 0 }}%
                            </div>
                            <div class="metric-label">Resolution Rate</div>
                        </div>
                        <i class="bi bi-percent fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric-card border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="metric-value">{{ $avgResolutionHours }} hrs</div>
                            <div class="metric-label">Avg Resolution Time</div>
                        </div>
                        <i class="bi bi-stopwatch fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>

                @if (auth()->user()->hasAnyRole([App\Enums\UserRole::SuperAdmin->value, App\Enums\UserRole::CentralEdp->value]))
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->code }} - {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach (App\Enums\TicketStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">Priority</label>
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">All Priorities</option>
                        @foreach (App\Enums\TicketPriority::cases() as $pr)
                            <option value="{{ $pr->value }}" {{ request('priority') === $pr->value ? 'selected' : '' }}>
                                {{ $pr->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4 mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-filter me-1"></i> Apply Filters
                    </button>
                    @if (request()->hasAny(['start_date', 'end_date', 'branch_id', 'category_id', 'status', 'priority', 'search']))
                        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Analytics Breakdown Row -->
    <div class="row g-4 mb-4">
        <!-- Breakdown by Branch -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-buildings text-primary me-2"></i>Tickets by Branch
                </div>
                <div class="card-body p-3">
                    @forelse ($ticketsByBranch as $tb)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="badge bg-light text-dark border font-monospace me-1">{{ $tb->code }}</span>
                                <span class="small">{{ $tb->name }}</span>
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $tb->count }}</span>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted small">No data for selected filters.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Breakdown by Category -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-tags text-indigo me-2"></i>Tickets by Category
                </div>
                <div class="card-body p-3">
                    @forelse ($ticketsByCategory as $tc)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small">{{ $tc->name }}</span>
                            <span class="badge bg-indigo rounded-pill text-white">{{ $tc->count }}</span>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted small">No data for selected filters.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Breakdown by Priority -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-bar-chart-line text-warning me-2"></i>Tickets by Priority
                </div>
                <div class="card-body p-3">
                    @foreach (App\Enums\TicketPriority::cases() as $p)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="badge {{ $p->badgeClass() }} me-2">&bull;</span>
                                <span class="small">{{ $p->label() }}</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill">
                                {{ $ticketsByPriority[$p->value] ?? 0 }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Paginated Details Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Filtered Ticket Records ({{ $tickets->total() }})</span>
            <a href="{{ route('reports.export-csv', request()->query()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-download me-1"></i> Export Filtered CSV
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th style="width: 130px;">Ticket #</th>
                        <th>Branch</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Reporter</th>
                        <th>Assigned EDP</th>
                        <th>Created</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $t)
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show', $t) }}" class="fw-bold font-monospace text-decoration-none">
                                    {{ $t->ticket_number }}
                                </a>
                            </td>
                            <td><span class="badge bg-secondary font-monospace">{{ $t->branch->code }}</span></td>
                            <td>{{ $t->category->name ?? 'General' }}</td>
                            <td>
                                <div class="text-truncate fw-semibold" style="max-width: 250px;">{{ $t->subject }}</div>
                            </td>
                            <td><span class="badge {{ $t->priority->badgeClass() }}">{{ $t->priority->label() }}</span></td>
                            <td><span class="badge {{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span></td>
                            <td>{{ $t->creator->name }}</td>
                            <td>{{ $t->assignedEdp->name ?? 'Unassigned' }}</td>
                            <td class="text-muted">{{ $t->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('tickets.show', $t) }}" class="btn btn-xs btn-outline-secondary py-0">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No tickets match the selected report criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
