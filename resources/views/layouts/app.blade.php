<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Centralized IT Helpdesk System') }}</title>

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="helpdesk-wrapper">
        <!-- Sidebar Navigation -->
        @include('layouts.sidebar')

        <!-- Main Content Area -->
        <div class="helpdesk-main-content">
            <!-- Top Navigation Bar -->
            @include('layouts.topbar')

            <!-- Page Body -->
            <main class="helpdesk-page-body">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>{{ session('status') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Page Header (Optional) -->
                @isset($header)
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        {{ $header }}
                    </div>
                @endisset

                <!-- Main Content Slot -->
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Global Flash Messages for SweetAlert -->
    <script>
        window.flashMessages = {
            success: @json(session('success')),
            error: @json(session('error')),
            validationErrors: @json($errors->all())
        };
    </script>

    @stack('scripts')
</body>
</html>
