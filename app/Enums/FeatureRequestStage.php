<?php

namespace App\Enums;

enum FeatureRequestStage: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Planning = 'planning';
    case Development = 'development';
    case Testing = 'testing';
    case Deployment = 'deployment';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Planning => 'Planning',
            self::Development => 'Development',
            self::Testing => 'Testing / QA',
            self::Deployment => 'Deployment',
            self::Completed => 'Completed',
        };
    }
}
