<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Feature Requests Management</h4>
            <div class="text-muted small">Software change requests, business proposals, and development lifecycle tracking</div>
        </div>
        <div>
            <a href="{{ route('tickets.create') }}?type=feature_request" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Propose Feature
            </a>
        </div>
    </x-slot>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('feature-requests.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search ticket # or specification..." value="{{ request('search') }}">
                </div>

                <div class="col-6 col-md-2">
                    <select name="stage" class="form-select form-select-sm">
                        <option value="">All Stages</option>
                        @foreach ($stages as $s)
                            <option value="{{ $s->value }}" {{ request('stage') === $s->value ? 'selected' : '' }}>
                                {{ $s->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select name="dev_priority" class="form-select form-select-sm">
                        <option value="">All Dev Priorities</option>
                        <option value="low" {{ request('dev_priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ request('dev_priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ request('dev_priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ request('dev_priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>

                @if (auth()->user()->hasAnyRole([App\Enums\UserRole::SuperAdmin->value, App\Enums\UserRole::CentralEdp->value]))
                    <div class="col-6 col-md-3">
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

                <div class="col-auto ms-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    @if (request()->hasAny(['search', 'stage', 'dev_priority', 'branch_id']))
                        <a href="{{ route('feature-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Feature Requests List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Feature Proposals ({{ $featureRequests->total() }})</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 140px;">Ticket #</th>
                        <th>Feature Proposal / Subject</th>
                        <th>Originating Branch</th>
                        <th style="width: 120px;">Dev Priority</th>
                        <th style="width: 130px;">Effort</th>
                        <th style="width: 150px;">Stage</th>
                        <th style="width: 130px;">Submitted</th>
                        <th style="width: 80px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($featureRequests as $fr)
                        <tr>
                            <td>
                                <a href="{{ route('feature-requests.show', $fr) }}" class="fw-semibold text-primary text-decoration-none">
                                    {{ $fr->ticket->ticket_number }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $fr->ticket->subject }}</div>
                                <div class="text-muted small text-truncate" style="max-width: 450px;">
                                    {{ \Illuminate\Support\Str::limit($fr->business_requirements, 90) }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $fr->ticket->branch->code }}
                                </span>
                            </td>
                            <td>
                                @if ($fr->dev_priority)
                                    <span class="badge bg-{{ match($fr->dev_priority) { 'critical' => 'danger', 'high' => 'warning text-dark', 'medium' => 'info text-dark', default => 'secondary' } }}">
                                        {{ ucfirst($fr->dev_priority) }}
                                    </span>
                                @else
                                    <span class="text-muted small">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <span class="small text-muted">{{ $fr->estimated_effort ?? 'TBD' }}</span>
                            </td>
                            <td>
                                <span class="{{ $fr->stage->badgeClass() }}">
                                    {{ $fr->stage->label() }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                {{ $fr->created_at->format('d M Y') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('feature-requests.show', $fr) }}" class="btn btn-outline-secondary btn-sm" title="View Feature Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-lightbulb fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No feature requests found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($featureRequests->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $featureRequests->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
