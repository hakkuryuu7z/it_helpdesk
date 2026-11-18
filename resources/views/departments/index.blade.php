<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Department Management</h4>
            <div class="text-muted small">Configure departments and operational units per branch</div>
        </div>
        @can('create', App\Models\Department::class)
            <div>
                <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Add Department
                </a>
            </div>
        @endcan
    </x-slot>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('departments.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search department name...">
                    </div>
                </div>

                @if ($availableBranches->count() > 1)
                    <div class="col-12 col-md-3">
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach ($availableBranches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->code }} - {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
                    @if (request()->hasAny(['search', 'branch_id']))
                        <a href="{{ route('departments.index') }}" class="btn btn-link btn-sm text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Branch</th>
                            <th class="text-center" style="width: 140px;">Users Count</th>
                            <th style="width: 120px;">Status</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($departments as $department)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $department->name }}</td>
                                <td>
                                    <span class="badge bg-secondary font-monospace me-1">{{ $department->branch->code }}</span>
                                    <span class="small text-muted">{{ $department->branch->name }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $department->users_count }}</span>
                                </td>
                                <td>
                                    @if ($department->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @can('update', $department)
                                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $department)
                                            <form method="POST" action="{{ route('departments.destroy', $department) }}" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this department?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No departments found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($departments->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $departments->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
