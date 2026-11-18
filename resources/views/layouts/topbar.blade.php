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
        @php
            $unreadCount = auth()->user()->unreadNotifications()->count();
            $recentNotifications = auth()->user()->unreadNotifications()->take(5)->get();
        @endphp
        <div class="dropdown">
            <button class="btn btn-sm btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell fs-6"></i>
                @if ($unreadCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 320px; max-width: 380px;">
                <li class="dropdown-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold">Notifications</span>
                    @if ($unreadCount > 0)
                        <span class="badge bg-primary-subtle text-primary">{{ $unreadCount }} unread</span>
                    @endif
                </li>
                <li><hr class="dropdown-divider my-0"></li>

                @forelse ($recentNotifications as $notif)
                    <li>
                        <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 px-3 text-wrap border-bottom bg-light">
                                <div class="fw-semibold text-dark small">{{ $notif->data['title'] ?? 'Notification' }}</div>
                                <div class="text-muted small text-truncate" style="max-width: 290px;">
                                    {{ $notif->data['message'] ?? '' }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    {{ $notif->created_at->diffForHumans() }}
                                </div>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="px-3 py-3 text-center text-muted small">
                        <i class="bi bi-bell-slash d-block fs-5 text-secondary opacity-50 mb-1"></i>
                        No unread notifications
                    </li>
                @endforelse

                <li><hr class="dropdown-divider my-0"></li>
                <li>
                    <a class="dropdown-item text-center small text-primary py-2 fw-semibold" href="{{ route('notifications.index') }}">
                        View All Notifications &rarr;
                    </a>
                </li>
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
