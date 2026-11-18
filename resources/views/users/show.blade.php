<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">User Profile: {{ $user->name }}</h4>
            <div class="text-muted small">Employee ID & Access Profile</div>
        </div>
        <div class="d-flex gap-2">
            @can('update', $user)
                <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit User
                </a>
            @endcan
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card text-center p-4 mb-4">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width: 72px; height: 72px; font-size: 1.75rem;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <div class="text-muted small mb-2">{{ $user->email }}</div>

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
                <div>
                    <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle fs-7">
                        {{ ucwords(str_replace('_', ' ', $role ?? 'regular_user')) }}
                    </span>
                </div>
            </div>

            <!-- Assigned Branches Card for EDP -->
            @if ($user->hasRole(\App\Enums\UserRole::BranchEdp->value))
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Authorized Branch Coverage</span>
                        <span class="badge bg-primary rounded-pill">{{ $user->assignedBranches->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush small">
                            @forelse ($user->assignedBranches as $b)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $b->name }}</span>
                                    <span class="badge bg-secondary font-monospace">{{ $b->code }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">
                                    No branch coverage assignments.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    Organizational Details
                </div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Primary Branch</span>
                            <span class="fw-semibold">
                                @if ($user->branch)
                                    <span class="badge bg-secondary font-monospace me-1">{{ $user->branch->code }}</span>
                                    {{ $user->branch->name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Department</span>
                            <span class="fw-semibold">{{ $user->department->name ?? '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Username</span>
                            <span class="font-monospace">{{ $user->username ?? '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Phone / WhatsApp</span>
                            <span>{{ $user->phone ?? '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Account Status</span>
                            <span>
                                @if ($user->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                @endif
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Registered Since</span>
                            <span>{{ $user->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Permissions List -->
            <div class="card">
                <div class="card-header">
                    Granted Permissions
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-1">
                        @forelse ($user->getAllPermissions() as $perm)
                            <span class="badge bg-light text-dark border small">{{ $perm->name }}</span>
                        @empty
                            <span class="text-muted small">No specific direct permissions assigned.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
