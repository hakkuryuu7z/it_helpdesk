<aside class="helpdesk-sidebar" id="sidebarMenu">
    <div class="brand-header">
        <i class="bi bi-shield-check text-primary me-2 fs-4"></i>
        <span>IT HELPDESK</span>
    </div>

    <div class="overflow-y-auto flex-grow-1 py-2">
        <!-- Overview -->
        <div class="nav-section-title">Overview</div>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- Ticket Management -->
        <div class="nav-section-title">IT Services & Tickets</div>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tickets.*') && !request()->routeIs('tickets.create') ? 'active' : '' }}" href="{{ Route::has('tickets.index') ? route('tickets.index') : '#' }}">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>All Tickets</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}" href="{{ Route::has('tickets.create') ? route('tickets.create') : '#' }}">
                    <i class="bi bi-plus-circle"></i>
                    <span>New Ticket</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('feature-requests.*') ? 'active' : '' }}" href="{{ Route::has('feature-requests.index') ? route('feature-requests.index') : '#' }}">
                    <i class="bi bi-lightbulb"></i>
                    <span>Feature Requests</span>
                </a>
            </li>
        </ul>

        <!-- Reports -->
        <div class="nav-section-title">Analytics & Reports</div>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ Route::has('reports.index') ? route('reports.index') : '#' }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Reports & Metrics</span>
                </a>
            </li>
        </ul>

        @php
            $currentUser = auth()->user();
            $canViewBranches = $currentUser?->can('viewAny', \App\Models\Branch::class);
            $canViewDepartments = $currentUser?->can('viewAny', \App\Models\Department::class);
            $canViewUsers = $currentUser?->can('viewAny', \App\Models\User::class);
            $canViewRoles = $currentUser?->hasRole(\App\Enums\UserRole::SuperAdmin->value) && Route::has('roles.index');
            $showAdminSection = $canViewBranches || $canViewDepartments || $canViewUsers || $canViewRoles;
        @endphp

        @if($showAdminSection)
        <!-- Master Data & Administration -->
        <div class="nav-section-title">Administration</div>
        <ul class="nav flex-column mb-3">
            @if($canViewBranches)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                    <i class="bi bi-building"></i>
                    <span>Branches</span>
                </a>
            </li>
            @endif
            @if($canViewDepartments)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">
                    <i class="bi bi-diagram-3"></i>
                    <span>Departments</span>
                </a>
            </li>
            @endif
            @if($canViewUsers)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-people"></i>
                    <span>User Management</span>
                </a>
            </li>
            @endif
            @if($canViewRoles)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                    <i class="bi bi-shield-lock"></i>
                    <span>Roles & Permissions</span>
                </a>
            </li>
            @endif
        </ul>
        @endif
    </div>

    <!-- Branch Status Footer -->
    <div class="p-3 border-top border-secondary border-opacity-25 small text-secondary">
        <div class="d-flex align-items-center justify-content-between">
            <span>Branch</span>
            <span class="badge bg-secondary text-uppercase">{{ auth()->user()->branch->code ?? 'HQ' }}</span>
        </div>
    </div>
</aside>
