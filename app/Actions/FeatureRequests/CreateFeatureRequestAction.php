<?php

namespace App\Actions\FeatureRequests;

use App\Enums\FeatureRequestStage;
use App\Models\FeatureRequest;
use App\Models\FeatureRequestActivity;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateFeatureRequestAction
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Create dedicated feature request record linked to parent ticket.
     */
    public function execute(Ticket $ticket, array $data, User $actor): FeatureRequest
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $featureRequest = FeatureRequest::create([
                'ticket_id' => $ticket->id,
                'business_requirements' => $data['business_requirements'] ?? $ticket->description,
                'problem_description' => $data['problem_description'] ?? $ticket->description,
                'expected_outcome' => $data['expected_outcome'] ?? $ticket->subject,
                'technical_notes' => $data['technical_notes'] ?? null,
                'dev_priority' => $data['dev_priority'] ?? null,
                'estimated_effort' => $data['estimated_effort'] ?? null,
                'stage' => FeatureRequestStage::Submitted,
            ]);

            FeatureRequestActivity::create([
                'feature_request_id' => $featureRequest->id,
                'user_id' => $actor->id,
                'from_stage' => null,
                'to_stage' => FeatureRequestStage::Submitted,
                'notes' => 'Feature request proposal registered and submitted for EDP review.',
                'created_at' => now(),
            ]);

            $this->auditService->logTicketAction(
                ticket: $ticket,
                action: 'feature_request_created',
                actor: $actor,
                newValues: [
                    'feature_request_id' => $featureRequest->id,
                    'stage' => FeatureRequestStage::Submitted->value,
                ]
            );

            return $featureRequest;
        });
    }
}
