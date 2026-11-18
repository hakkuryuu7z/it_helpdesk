<?php

namespace App\Enums;

enum TicketType: string
{
    case Incident = 'incident';
    case ServiceRequest = 'service_request';
    case FeatureRequest = 'feature_request';

    public function label(): string
    {
        return match ($this) {
            self::Incident => 'Incident / Complaint',
            self::ServiceRequest => 'Service Request',
            self::FeatureRequest => 'Feature Request',
        };
    }
}
