<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Register New User</h4>
            <div class="text-muted small">Create an employee account with branch and role assignment</div>
        </div>
        <div>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Users
            </a>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="card" x-data="{ selectedRole: '{{ old('role', 'regular_user') }}' }">
                <div class="card-header">
                    Account Information
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="row g-3">
                            <!-- Full Name -->
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. John Doe">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                       class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="john.doe@company.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Username -->
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label small fw-semibold">Username</label>
                                <input type="text" name="username" id="username" value="{{ old('username') }}"
                                       class="form-control form-control-sm @error('username') is-invalid @enderror" placeholder="e.g. jdoe">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label small fw-semibold">Phone / WhatsApp</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                       class="form-control form-control-sm @error('phone') is-invalid @enderror" placeholder="e.g. +62812345678">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Home Branch -->
                            <div class="col-12 col-md-6">
                                <label for="branch_id" class="form-label small fw-semibold">Primary Branch <span class="text-danger">*</span></label>
                                @if ($branches->count() === 1)
                                    <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
                                    <input type="text" class="form-control form-control-sm bg-light" value="{{ $branches->first()->code }} - {{ $branches->first()->name }}" readonly>
                                @else
                                    <select name="branch_id" id="branch_id" required class="form-select form-select-sm @error('branch_id') is-invalid @enderror">
                                        <option value="">Select Branch</option>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                                {{ $b->code }} - {{ $b->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Department -->
                            <div class="col-12 col-md-6">
                                <label for="department_id" class="form-label small fw-semibold">Department</label>
                                <select name="department_id" id="department_id" class="form-select form-select-sm @error('department_id') is-invalid @enderror">
                                    <option value="">Select Department (Optional)</option>
                                    @foreach ($departments as $d)
                                        <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>
                                            {{ $d->branch->code }} - {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- System Role -->
                            <div class="col-12 col-md-6">
                                <label for="role" class="form-label small fw-semibold">System Role <span class="text-danger">*</span></label>
                                <select name="role" id="role" x-model="selectedRole" required class="form-select form-select-sm @error('role') is-invalid @enderror">
                                    @foreach ($roles as $r)
                                        <option value="{{ $r->name }}" {{ old('role', 'regular_user') == $r->name ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $r->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Initial Password -->
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label small fw-semibold">Initial Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" id="password" required
                                       class="form-control form-control-sm @error('password') is-invalid @enderror" placeholder="Min. 8 characters">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Branch EDP Multiple Assignments -->
                            <div class="col-12" x-show="selectedRole === 'branch_edp'" x-cloak>
                                <div class="p-3 bg-light rounded border">
                                    <label class="form-label small fw-semibold d-block mb-1">
                                        <i class="bi bi-diagram-2 me-1 text-primary"></i> Designated Branch Coverage (Branch EDP)
                                    </label>
                                    <div class="text-muted small mb-2">Select all branches this EDP personnel is authorized to monitor and service:</div>
                                    <div class="row g-2">
                                        @foreach ($branches as $b)
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="assigned_branches[]"
                                                           value="{{ $b->id }}" id="branch_edp_{{ $b->id }}"
                                                           {{ is_array(old('assigned_branches')) && in_array($b->id, old('assigned_branches')) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="branch_edp_{{ $b->id }}">
                                                        <span class="badge bg-secondary font-monospace">{{ $b->code }}</span> {{ $b->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="is_active">User Account is Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-save me-1"></i> Register Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
