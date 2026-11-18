<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">User Management</h4>
            <div class="text-muted small">Manage staff accounts, organizational branches, and assigned system roles</div>
        </div>
        @can('create', App\Models\User::class)
            <div>
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-person-plus me-1"></i> Register User
                </a>
            </div>
        @endcan
    </x-slot>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search user name, email, username...">
                    </div>
                </div>

                @if ($branches->count() > 1)
                    <div class="col-12 col-md-3">
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

                <div class="col-12 col-md-3">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->name }}" {{ request('role') == $r->name ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $r->name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
                    @if (request()->hasAny(['search', 'branch_id', 'role']))
                        <a href="{{ route('users.index') }}" class="btn btn-link btn-sm text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th style="width: 100px;">Status</th>
                            <th class="text-end" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary fw-bold d-flex align-items-center justify-content-center"
                                             style="width: 34px; height: 34px; font-size: 0.85rem;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $user->name }}</div>
                                            <div class="text-muted small" style="font-size: 0.775rem;">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $role = $user->roles->first()?->name;
                                        $badgeColor = match($role) {
                                            'super_admin' => 'danger',
                                            'central_edp' => 'primary',
                                            'branch_edp' => 'info',
                                            'branch_admin' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle">
                                        {{ ucwords(str_replace('_', ' ', $role ?? 'regular_user')) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($user->branch)
                                        <span class="badge bg-secondary font-monospace">{{ $user->branch->code }}</span>
                                        <span class="small text-muted">{{ $user->branch->name }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-secondary">{{ $user->department->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $user->phone ?? '-' }}</span>
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('update', $user)
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $user)
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this user?');">
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
                                    No users found matching your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
