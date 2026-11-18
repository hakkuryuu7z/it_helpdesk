<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Create Support Ticket</h4>
            <div class="text-muted small">Submit an IT incident, request service assistance, or propose new features</div>
        </div>
        <div>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Tickets
            </a>
        </div>
    </x-slot>

    <div class="row justify-content-center" x-data="{ ticketType: '{{ old('type', request('type', 'incident')) }}' }">
        <div class="col-12 col-lg-9">
            <div class="card">
                <div class="card-header">
                    Ticket Details
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <!-- Ticket Type -->
                            <div class="col-12 col-md-6">
                                <label for="type" class="form-label small fw-semibold">Ticket Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" required x-model="ticketType"
                                        class="form-select form-select-sm @error('type') is-invalid @enderror">
                                    @foreach (App\Enums\TicketType::cases() as $t)
                                        <option value="{{ $t->value }}">
                                            {{ $t->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Category -->
                            <div class="col-12 col-md-6">
                                <label for="category_id" class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" required class="form-select form-select-sm @error('category_id') is-invalid @enderror">
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Originating Branch -->
                            <div class="col-12 col-md-6">
                                <label for="branch_id" class="form-label small fw-semibold">Originating Branch <span class="text-danger">*</span></label>
                                @if (auth()->user()->hasAnyRole([App\Enums\UserRole::SuperAdmin->value, App\Enums\UserRole::CentralEdp->value]) && $branches->count() > 1)
                                    <select name="branch_id" id="branch_id" required class="form-select form-select-sm @error('branch_id') is-invalid @enderror">
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}" {{ old('branch_id', auth()->user()->branch_id) == $b->id ? 'selected' : '' }}>
                                                {{ $b->code }} - {{ $b->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                                    <input type="text" class="form-control form-control-sm bg-light"
                                           value="{{ auth()->user()->branch->code ?? 'HQ' }} - {{ auth()->user()->branch->name ?? 'Head Office' }}" readonly>
                                @endif
                                <div class="form-text small">Tickets are strictly anchored to the reporting branch.</div>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Subject -->
                            <div class="col-12 col-md-6">
                                <label for="subject" class="form-label small fw-semibold">Subject / Brief Summary <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                       class="form-control form-control-sm @error('subject') is-invalid @enderror"
                                       placeholder="e.g. Printer offline / New stock adjustment report feature">
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Impact -->
                            <div class="col-12 col-md-6">
                                <label for="impact" class="form-label small fw-semibold">Impact Assessment <span class="text-danger">*</span></label>
                                <select name="impact" id="impact" required class="form-select form-select-sm @error('impact') is-invalid @enderror">
                                    @foreach (App\Enums\TicketImpact::cases() as $imp)
                                        <option value="{{ $imp->value }}" {{ old('impact', 'low') === $imp->value ? 'selected' : '' }}>
                                            {{ $imp->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small">How widely does this issue/request affect operations?</div>
                                @error('impact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Urgency -->
                            <div class="col-12 col-md-6">
                                <label for="urgency" class="form-label small fw-semibold">Urgency Assessment <span class="text-danger">*</span></label>
                                <select name="urgency" id="urgency" required class="form-select form-select-sm @error('urgency') is-invalid @enderror">
                                    @foreach (App\Enums\TicketUrgency::cases() as $urg)
                                        <option value="{{ $urg->value }}" {{ old('urgency', 'low') === $urg->value ? 'selected' : '' }}>
                                            {{ $urg->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small">How quickly is IT intervention required?</div>
                                @error('urgency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label small fw-semibold">Detailed Description <span class="text-danger">*</span></label>
                                <textarea name="description" id="description" rows="4" required
                                          class="form-control form-control-sm @error('description') is-invalid @enderror"
                                          placeholder="Provide complete troubleshooting details, error messages, device IP or host name, steps to reproduce...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Feature Request Dedicated Specification (Visible when type === 'feature_request') -->
                            <div class="col-12" x-show="ticketType === 'feature_request'" x-cloak x-transition>
                                <div class="p-3 bg-light rounded border border-primary border-opacity-25">
                                    <div class="d-flex align-items-center gap-2 mb-3 text-primary fw-bold">
                                        <i class="bi bi-lightbulb-fill"></i>
                                        <span>Feature Proposal Specification (Required for Feature Requests)</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="problem_description" class="form-label small fw-semibold">1. Current Problem / Workflow Limitation</label>
                                            <textarea name="problem_description" id="problem_description" rows="2"
                                                      class="form-control form-control-sm"
                                                      placeholder="What is currently difficult, slow, or missing in the existing software?">{{ old('problem_description') }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="business_requirements" class="form-label small fw-semibold">2. Business Requirements</label>
                                            <textarea name="business_requirements" id="business_requirements" rows="3"
                                                      class="form-control form-control-sm"
                                                      placeholder="Describe the desired business flow, user inputs, calculations, and rules...">{{ old('business_requirements') }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="expected_outcome" class="form-label small fw-semibold">3. Expected Outcome / Value</label>
                                            <textarea name="expected_outcome" id="expected_outcome" rows="2"
                                                      class="form-control form-control-sm"
                                                      placeholder="What benefits will this provide (e.g. time saved, error prevention, reporting clarity)?">{{ old('expected_outcome') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attachment -->
                            <div class="col-12">
                                <label for="attachment" class="form-label small fw-semibold">Attachment (Screenshot, Log, or Mockup)</label>
                                <input type="file" name="attachment" id="attachment"
                                       class="form-control form-control-sm @error('attachment') is-invalid @enderror">
                                <div class="form-text small">Allowed formats: PNG, JPG, PDF, Word, Excel, CSV, ZIP. Max size: 10 MB. Executables strictly rejected.</div>
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-send me-1"></i> Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
