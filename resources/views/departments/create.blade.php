<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Create Department</h4>
            <div class="text-muted small">Add a new operational department to a branch</div>
        </div>
        <div>
            <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Departments
            </a>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-header">
                    Department Details
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('departments.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="branch_id" class="form-label small fw-semibold">Branch <span class="text-danger">*</span></label>
                            @if ($branches->count() === 1)
                                <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
                                <input type="text" class="form-control form-control-sm bg-light" value="{{ $branches->first()->code }} - {{ $branches->first()->name }}" readonly>
                            @else
                                <select name="branch_id" id="branch_id" required class="form-select form-select-sm @error('branch_id') is-invalid @enderror">
                                    <option value="">Select Branch</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" {{ old('branch_id', request('branch_id')) == $b->id ? 'selected' : '' }}>
                                            {{ $b->code }} - {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold">Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. Finance & Accounting">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="is_active">Active Department</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-save me-1"></i> Save Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
