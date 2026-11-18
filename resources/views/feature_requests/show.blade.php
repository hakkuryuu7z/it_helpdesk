<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="fw-bold mb-0">Feature Request: {{ $featureRequest->ticket->ticket_number }}</h4>
                    <span class="{{ $featureRequest->stage->badgeClass() }} fs-6">
                        {{ $featureRequest->stage->label() }}
                    </span>
                </div>
                <div class="text-muted small">Linked to Ticket #{{ $featureRequest->ticket->ticket_number }} &bull; Branch: {{ $featureRequest->ticket->branch->name }}</div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tickets.show', $featureRequest->ticket) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i> View Parent Ticket
            </a>
            <a href="{{ route('feature-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Features
            </a>
        </div>
    </x-slot>

    <div class="row g-4">
        <!-- Main Column: Specifications & Timeline -->
        <div class="col-12 col-lg-8">
            <!-- Business Specification Card -->
            <div class="card mb-4">
                <div class="card-header bg-white fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text text-primary"></i>
                    Business Requirements Specification
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Proposed Feature / Subject</label>
                        <div class="p-2 bg-light rounded fw-semibold text-dark">
                            {{ $featureRequest->ticket->subject }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted text-uppercase">1. Problem Description / Current Limitation</label>
                        <div class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;">{{ $featureRequest->problem_description }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted text-uppercase">2. Business Requirements & User Workflow</label>
                        <div class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;">{{ $featureRequest->business_requirements }}</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-muted text-uppercase">3. Expected Outcome & Acceptance Criteria</label>
                        <div class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;">{{ $featureRequest->expected_outcome }}</div>
                    </div>
                </div>
            </div>

            <!-- Technical & Deployment Details Card (if present) -->
            @if ($featureRequest->technical_notes || $featureRequest->testing_notes || $featureRequest->deployment_notes)
                <div class="card mb-4">
                    <div class="card-header bg-white fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-code-slash text-indigo"></i>
                        Technical Architecture & Deployment Notes
                    </div>
                    <div class="card-body">
                        @if ($featureRequest->technical_notes)
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted text-uppercase">Engineering & Implementation Plan</label>
                                <div class="p-3 bg-light rounded font-monospace small" style="white-space: pre-wrap;">{{ $featureRequest->technical_notes }}</div>
                            </div>
                        @endif

                        @if ($featureRequest->testing_notes)
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted text-uppercase">Quality Assurance & Test Criteria</label>
                                <div class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;">{{ $featureRequest->testing_notes }}</div>
                            </div>
                        @endif

                        @if ($featureRequest->deployment_notes)
                            <div>
                                <label class="form-label small fw-semibold text-muted text-uppercase">Production Release & Migration Notes</label>
                                <div class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;">{{ $featureRequest->deployment_notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Lifecycle Activity History -->
            <div class="card">
                <div class="card-header bg-white fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-secondary"></i>
                    Lifecycle Stage Transition History
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($featureRequest->activities as $act)
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($act->from_stage)
                                            <span class="badge bg-secondary small">{{ $act->from_stage->label() }}</span>
                                            <i class="bi bi-arrow-right text-muted small"></i>
                                        @endif
                                        <span class="{{ $act->to_stage->badgeClass() }} small">{{ $act->to_stage->label() }}</span>
                                    </div>
                                    <small class="text-muted">{{ $act->created_at->format('d M Y, H:i') }}</small>
                                </div>
                                <div class="small text-muted mb-1">
                                    By: <strong>{{ $act->user->name ?? 'System' }}</strong>
                                </div>
                                @if ($act->notes)
                                    <div class="p-2 bg-light rounded small mt-1 text-dark">
                                        {{ $act->notes }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted small">No stage transitions logged yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column: Metadata & EDP Controls -->
        <div class="col-12 col-lg-4">
            <!-- Metadata Summary Card -->
            <div class="card mb-4">
                <div class="card-header bg-white fw-bold">
                    Feature Metadata
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Parent Ticket</span>
                        <a href="{{ route('tickets.show', $featureRequest->ticket) }}" class="fw-bold small text-decoration-none">
                            {{ $featureRequest->ticket->ticket_number }}
                        </a>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Originating Branch</span>
                        <span class="fw-semibold small">{{ $featureRequest->ticket->branch->code }} ({{ $featureRequest->ticket->branch->name }})</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Submitted By</span>
                        <span class="small">{{ $featureRequest->ticket->creator->name }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Dev Priority</span>
                        @if ($featureRequest->dev_priority)
                            <span class="badge bg-{{ match($featureRequest->dev_priority) { 'critical' => 'danger', 'high' => 'warning text-dark', 'medium' => 'info text-dark', default => 'secondary' } }}">
                                {{ ucfirst($featureRequest->dev_priority) }}
                            </span>
                        @else
                            <span class="text-muted small">Not set</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Estimated Effort</span>
                        <span class="small fw-semibold">{{ $featureRequest->estimated_effort ?? 'Not estimated' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Ticket Status</span>
                        <span class="{{ $featureRequest->ticket->status->badgeClass() }} small">
                            {{ $featureRequest->ticket->status->label() }}
                        </span>
                    </div>
                    @if ($featureRequest->completed_at)
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted small">Completed Date</span>
                            <span class="small text-success fw-bold">{{ $featureRequest->completed_at->format('d M Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stage Management Card (Central EDP & Super Admin) -->
            @can('updateStage', $featureRequest)
                <div class="card border-primary border-opacity-50">
                    <div class="card-header bg-primary text-white fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-kanban"></i>
                        Lifecycle Transition
                    </div>
                    <div class="card-body p-3">
                        <form method="POST" action="{{ route('feature-requests.update-stage', $featureRequest) }}">
                            @csrf
                            @method('PATCH')

                            <!-- Target Stage -->
                            <div class="mb-3">
                                <label for="stage" class="form-label small fw-semibold">Next Stage <span class="text-danger">*</span></label>
                                <select name="stage" id="stage" required class="form-select form-select-sm @error('stage') is-invalid @enderror">
                                    <option value="{{ $featureRequest->stage->value }}" selected>
                                        Current: {{ $featureRequest->stage->label() }}
                                    </option>
                                    @foreach ($featureRequest->stage->allowedTransitions() as $next)
                                        <option value="{{ $next->value }}">
                                            &rarr; {{ $next->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('stage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Development Priority -->
                            <div class="mb-3">
                                <label for="dev_priority" class="form-label small fw-semibold">Dev Priority</label>
                                <select name="dev_priority" id="dev_priority" class="form-select form-select-sm">
                                    <option value="">Select Priority</option>
                                    <option value="low" {{ old('dev_priority', $featureRequest->dev_priority) === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('dev_priority', $featureRequest->dev_priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('dev_priority', $featureRequest->dev_priority) === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ old('dev_priority', $featureRequest->dev_priority) === 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                            </div>

                            <!-- Estimated Effort -->
                            <div class="mb-3">
                                <label for="estimated_effort" class="form-label small fw-semibold">Estimated Effort</label>
                                <input type="text" name="estimated_effort" id="estimated_effort"
                                       value="{{ old('estimated_effort', $featureRequest->estimated_effort) }}"
                                       class="form-control form-control-sm"
                                       placeholder="e.g. 2 Sprints / 40h">
                            </div>

                            <!-- Technical Notes -->
                            <div class="mb-3">
                                <label for="technical_notes" class="form-label small fw-semibold">Technical Architecture Notes</label>
                                <textarea name="technical_notes" id="technical_notes" rows="2"
                                          class="form-control form-control-sm"
                                          placeholder="Architecture, libraries, database changes...">{{ old('technical_notes', $featureRequest->technical_notes) }}</textarea>
                            </div>

                            <!-- Testing Notes -->
                            <div class="mb-3">
                                <label for="testing_notes" class="form-label small fw-semibold">Testing / QA Notes</label>
                                <textarea name="testing_notes" id="testing_notes" rows="2"
                                          class="form-control form-control-sm"
                                          placeholder="Test criteria, edge cases...">{{ old('testing_notes', $featureRequest->testing_notes) }}</textarea>
                            </div>

                            <!-- Deployment Notes -->
                            <div class="mb-3">
                                <label for="deployment_notes" class="form-label small fw-semibold">Deployment Notes</label>
                                <textarea name="deployment_notes" id="deployment_notes" rows="2"
                                          class="form-control form-control-sm"
                                          placeholder="Release version, migration steps...">{{ old('deployment_notes', $featureRequest->deployment_notes) }}</textarea>
                            </div>

                            <!-- Transition Comment -->
                            <div class="mb-3">
                                <label for="notes" class="form-label small fw-semibold">Activity Comment / Reason</label>
                                <textarea name="notes" id="notes" rows="2"
                                          class="form-control form-control-sm"
                                          placeholder="Reason for stage change..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-arrow-repeat me-1"></i> Update Stage
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
