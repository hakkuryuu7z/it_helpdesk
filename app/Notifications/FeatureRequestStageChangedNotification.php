<?php

namespace App\Notifications;

use App\Enums\FeatureRequestStage;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FeatureRequestStageChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public FeatureRequest $featureRequest,
        public FeatureRequestStage $toStage,
        public User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->featureRequest->ticket->id,
            'ticket_number' => $this->featureRequest->ticket->ticket_number,
            'feature_request_id' => $this->featureRequest->id,
            'title' => "Feature Proposal Stage: {$this->toStage->label()}",
            'message' => "Proposal for ticket #{$this->featureRequest->ticket->ticket_number} advanced to stage '{$this->toStage->label()}'.",
            'url' => route('feature-requests.show', $this->featureRequest),
            'type' => 'feature_stage_changed',
        ];
    }
}
