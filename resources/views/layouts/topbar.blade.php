<header class="helpdesk-topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-light d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Breadcrumbs / Page Title -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                @if (isset($breadcrumbs))
                    {{ $breadcrumbs }}
                @else
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $title ?? 'Dashboard' }}</li>
                @endif
            </ol>
        </nav>
    </div>

    <!-- Right Header Elements -->
    <div class="d-flex align-items-center gap-3">
        <!-- Notification Dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell fs-6"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                    <span class="visually-hidden">New alerts</span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 280px;">
                <li class="dropdown-header fw-bold">Notifications</li>
                <li><hr class="dropdown-divider my-1"></li>
                <li class="px-3 py-2 text-center text-muted small">No unread notifications</li>
            </ul>
        </div>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem;">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="text-start d-none d-sm-block">
                    <div class="fw-semibold small leading-tight">{{ Auth::user()->name ?? 'User' }}</div>
                    <div class="text-muted" style="font-size: 0.725rem;">{{ Auth::user()->getRoleNames()->first() ?? 'Employee' }}</div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><h6 class="dropdown-header small text-muted">{{ Auth::user()->email ?? '' }}</h6></li>
                <li>
                    <a class="dropdown-item small" href="{{ route('profile.edit') }}">
                        <i class="bi bi-person me-2"></i>My Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item small text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Log Out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
