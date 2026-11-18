<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Branch Management</h4>
            <div class="text-muted small">Manage organizational branch locations and EDP coverage</div>
        </div>
        @can('create', App\Models\Branch::class)
            <div>
                <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Add Branch
                </a>
            </div>
        @endcan
    </x-slot>

    <!-- Search & Filter Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('branches.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search branch name, code, or city...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
                    @if (request()->hasAny(['search']))
                        <a href="{{ route('branches.index') }}" class="btn btn-link btn-sm text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Branches Table Card -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Code</th>
                            <th>Branch Name</th>
                            <th>City / Location</th>
                            <th class="text-center" style="width: 140px;">Departments</th>
                            <th class="text-center" style="width: 120px;">Staff</th>
                            <th style="width: 100px;">Status</th>
                            <th class="text-end" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr>
                                <td><span class="badge bg-secondary font-monospace">{{ $branch->code }}</span></td>
                                <td class="fw-semibold text-dark">{{ $branch->name }}</td>
                                <td class="text-muted small">{{ $branch->city ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $branch->departments_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $branch->users_count }}</span>
                                </td>
                                <td>
                                    @if ($branch->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('branches.show', $branch) }}" class="btn btn-outline-secondary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('update', $branch)
                                            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $branch)
                                            <form method="POST" action="{{ route('branches.destroy', $branch) }}" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to remove this branch?');">
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
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No branches found matching your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($branches->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
