<?php

namespace App\Actions\FeatureRequests;

use App\Enums\FeatureRequestStage;
use App\Enums\TicketStatus;
use App\Models\FeatureRequest;
use App\Models\FeatureRequestActivity;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TicketWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionFeatureStageAction
{
    public function __construct(
        protected AuditService $auditService,
        protected TicketWorkflowService $ticketWorkflowService,
    ) {}

    /**
     * Transition a feature request stage with validation, history, and ticket lifecycle sync.
     */
    public function execute(FeatureRequest $featureRequest, FeatureRequestStage $toStage, array $data, User $actor): FeatureRequest
    {
        $fromStage = $featureRequest->stage;

        if ($fromStage === $toStage) {
            // No stage change, just updating notes or metadata
            $this->updateMetadata($featureRequest, $data);
            $featureRequest->save();
            return $featureRequest;
        }

        if (!$fromStage->canTransitionTo($toStage)) {
            throw ValidationException::withMessages([
                'stage' => "Cannot transition feature request from '{$fromStage->label()}' to '{$toStage->label()}'.",
            ]);
        }

        return DB::transaction(function () use ($featureRequest, $fromStage, $toStage, $data, $actor) {
            $featureRequest->stage = $toStage;
            $this->updateMetadata($featureRequest, $data);

            if ($toStage === FeatureRequestStage::Completed) {
                $featureRequest->completed_at = now();
            }

            $featureRequest->save();

            // Record stage activity
            FeatureRequestActivity::create([
                'feature_request_id' => $featureRequest->id,
                'user_id' => $actor->id,
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
                'notes' => $data['notes'] ?? "Transitioned stage from {$fromStage->label()} to {$toStage->label()}.",
                'created_at' => now(),
            ]);

            // Audit history on parent ticket
            $ticket = $featureRequest->ticket;
            $this->auditService->logTicketAction(
                ticket: $ticket,
                action: 'feature_stage_transition',
                actor: $actor,
                oldValues: ['stage' => $fromStage->value],
                newValues: [
                    'stage' => $toStage->value,
                    'notes' => $data['notes'] ?? null,
                ]
            );

            // Synchronize parent ticket status when appropriate
            if ($toStage === FeatureRequestStage::Development && $ticket->status !== TicketStatus::InProgress) {
                if ($this->ticketWorkflowService->canTransition($ticket, TicketStatus::InProgress, $actor)) {
                    $ticket->status = TicketStatus::InProgress;
                    $ticket->save();
                }
            } elseif ($toStage === FeatureRequestStage::Completed && $ticket->status !== TicketStatus::Resolved && $ticket->status !== TicketStatus::Closed) {
                $ticket->status = TicketStatus::Resolved;
                $ticket->resolved_at = now();
                $ticket->resolution_notes = $data['notes'] ?? 'Feature request successfully implemented, tested, and deployed to production.';
                $ticket->save();
            } elseif ($toStage === FeatureRequestStage::Rejected && $ticket->status !== TicketStatus::Rejected && $ticket->status !== TicketStatus::Closed) {
                $ticket->status = TicketStatus::Rejected;
                $ticket->resolution_notes = $data['notes'] ?? 'Feature request rejected during technical review.';
                $ticket->save();
            }

            return $featureRequest;
        });
    }

    private function updateMetadata(FeatureRequest $featureRequest, array $data): void
    {
        if (isset($data['technical_notes'])) {
            $featureRequest->technical_notes = $data['technical_notes'];
        }
        if (isset($data['dev_priority'])) {
            $featureRequest->dev_priority = $data['dev_priority'];
        }
        if (isset($data['estimated_effort'])) {
            $featureRequest->estimated_effort = $data['estimated_effort'];
        }
        if (isset($data['testing_notes'])) {
            $featureRequest->testing_notes = $data['testing_notes'];
        }
        if (isset($data['deployment_notes'])) {
            $featureRequest->deployment_notes = $data['deployment_notes'];
        }
    }
}
