<x-guest-layout>
    <div class="text-center mb-4">
        <h5 class="fw-bold text-dark">Register Employee Account</h5>
        <div class="text-muted small">Create an account to submit support tickets and track IT requests</div>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label small fw-semibold text-secondary">Full Name</label>
            <div class="input-group">
                <span class="input-group-text bg-white text-muted"><i class="bi bi-person"></i></span>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                       class="form-control @error('name') is-invalid @enderror" placeholder="John Doe">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold text-secondary">Work Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-white text-muted"><i class="bi bi-envelope"></i></span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                       class="form-control @error('email') is-invalid @enderror" placeholder="name@company.com">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-white text-muted"><i class="bi bi-lock"></i></span>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="form-control @error('password') is-invalid @enderror" placeholder="Minimum 8 characters">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label small fw-semibold text-secondary">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text bg-white text-muted"><i class="bi bi-shield-lock"></i></span>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="form-control @error('password_confirmation') is-invalid @enderror" placeholder="Repeat your password">
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
            <i class="bi bi-person-plus me-1"></i> Create Account
        </button>

        <div class="text-center small text-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Sign in here</a>
        </div>
    </form>
</x-guest-layout>
