<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Branch Overview: {{ $branch->name }}</h4>
            <div class="text-muted small">Branch code: <span class="badge bg-secondary font-monospace">{{ $branch->code }}</span></div>
        </div>
        <div class="d-flex gap-2">
            @can('update', $branch)
                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit Branch
                </a>
            @endcan
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </x-slot>

    <div class="row g-4">
        <!-- Branch Details -->
        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    Branch Summary
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Code</span>
                            <span class="font-monospace fw-bold">{{ $branch->code }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">City</span>
                            <span>{{ $branch->city ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Status</span>
                            <span>
                                @if ($branch->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Registered Employees</span>
                            <span class="fw-bold">{{ $usersCount }}</span>
                        </li>
                    </ul>

                    @if ($branch->address)
                        <div class="mt-3 pt-2 border-top">
                            <div class="small fw-semibold text-muted mb-1">Address</div>
                            <div class="small text-secondary">{{ $branch->address }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Assigned Branch EDPs -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Designated EDP Staff</span>
                    <span class="badge bg-primary rounded-pill">{{ $branch->assignedEdps->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        @forelse ($branch->assignedEdps as $edp)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $edp->name }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $edp->email }}</div>
                                </div>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">EDP</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center py-3">
                                No dedicated Branch EDP assigned yet.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Departments List -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Departments in {{ $branch->name }}</span>
                    @can('create', App\Models\Department::class)
                        <a href="{{ route('departments.create', ['branch_id' => $branch->id]) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Add Department
                        </a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Department Name</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branch->departments as $dept)
                                    <tr>
                                        <td class="fw-semibold text-dark">{{ $dept->name }}</td>
                                        <td>
                                            @if ($dept->is_active)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @can('update', $dept)
                                                <a href="{{ route('departments.edit', $dept) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            No departments configured for this branch.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
