<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Ticket Management</h4>
            <div class="text-muted small">Submit, track, and resolve IT service requests and technical incidents</div>
        </div>
        <div>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Submit New Ticket
            </a>
        </div>
    </x-slot>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('tickets.index') }}" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Ticket #, subject, text...">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach (App\Enums\TicketStatus::cases() as $s)
                            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>
                                {{ $s->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="col-6 col-md-2">
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">All Priorities</option>
                        @foreach (App\Enums\TicketPriority::cases() as $p)
                            <option value="{{ $p->value }}" {{ request('priority') === $p->value ? 'selected' : '' }}>
                                {{ $p->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="col-6 col-md-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach (App\Enums\TicketType::cases() as $t)
                            <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>
                                {{ $t->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Branch Filter -->
                @if ($branches->count() > 1)
                    <div class="col-6 col-md-2">
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

                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
                    @if (request()->hasAny(['search', 'status', 'priority', 'type', 'branch_id']))
                        <a href="{{ route('tickets.index') }}" class="btn btn-link btn-sm text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table Card -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Ticket #</th>
                            <th>Subject & Category</th>
                            <th style="width: 110px;">Branch</th>
                            <th style="width: 140px;">Creator</th>
                            <th style="width: 140px;">Assignee</th>
                            <th style="width: 100px;">Priority</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 110px;">Date</th>
                            <th class="text-end" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}" class="fw-bold text-decoration-none font-monospace text-primary">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                    <div class="mt-1">
                                        <span class="badge {{ $ticket->type->value === 'incident' ? 'badge-type-incident' : ($ticket->type->value === 'feature_request' ? 'badge-type-feature_request' : 'badge-type-service_request') }}" style="font-size: 0.675rem;">
                                            {{ $ticket->type->label() }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}" class="text-dark fw-semibold text-decoration-none d-block mb-1">
                                        {{ $ticket->subject }}
                                    </a>
                                    <div class="text-muted small" style="font-size: 0.775rem;">
                                        <i class="bi bi-tag me-1"></i>{{ $ticket->category->name ?? 'General' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary font-monospace">{{ $ticket->branch->code }}</span>
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ $ticket->branch->city }}</div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $ticket->creator->name }}</div>
                                    <div class="text-muted" style="font-size: 0.725rem;">{{ $ticket->creator->email }}</div>
                                </td>
                                <td>
                                    @if ($ticket->assignedEdp)
                                        <div class="small fw-semibold text-primary">{{ $ticket->assignedEdp->name }}</div>
                                        <div class="text-muted" style="font-size: 0.725rem;">EDP Technician</div>
                                    @else
                                        <span class="badge bg-light text-muted border">Unassigned</span>
                                    @endif
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
                                    <div class="small text-secondary">{{ $ticket->created_at->format('d M Y') }}</div>
                                    <div class="text-muted" style="font-size: 0.725rem;">{{ $ticket->created_at->format('H:i') }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-secondary" title="View Ticket">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-ticket-perforated fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No tickets found matching your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($tickets->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
