<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Create New Branch</h4>
            <div class="text-muted small">Register a new physical office or branch location</div>
        </div>
        <div>
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Branches
            </a>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    Branch Information
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('branches.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="code" class="form-label small fw-semibold">Branch Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code') }}" required
                                       class="form-control form-control-sm @error('code') is-invalid @enderror" placeholder="e.g. BDG">
                                <div class="form-text small">Unique short identifier (e.g., HQ, BDG, SBY).</div>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-8">
                                <label for="name" class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. Branch Bandung">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="city" class="form-label small fw-semibold">City</label>
                                <input type="text" name="city" id="city" value="{{ old('city') }}"
                                       class="form-control form-control-sm @error('city') is-invalid @enderror" placeholder="e.g. Bandung">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold d-block">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="is_active">Active & Operational</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label small fw-semibold">Full Address</label>
                                <textarea name="address" id="address" rows="3"
                                          class="form-control form-control-sm @error('address') is-invalid @enderror"
                                          placeholder="Street name, building, district...">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-save me-1"></i> Save Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
