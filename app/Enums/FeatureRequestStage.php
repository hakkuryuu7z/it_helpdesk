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

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'badge bg-secondary',
            self::UnderReview => 'badge bg-info text-dark',
            self::Accepted => 'badge bg-primary',
            self::Rejected => 'badge bg-danger',
            self::Planning => 'badge bg-warning text-dark',
            self::Development => 'badge bg-indigo text-white',
            self::Testing => 'badge bg-cyan text-dark',
            self::Deployment => 'badge bg-warning text-dark',
            self::Completed => 'badge bg-success',
        };
    }

    /**
     * Define valid stage transitions.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::Rejected],
            self::UnderReview => [self::Accepted, self::Rejected, self::Planning],
            self::Accepted => [self::Planning, self::UnderReview],
            self::Planning => [self::Development, self::Accepted],
            self::Development => [self::Testing, self::Planning],
            self::Testing => [self::Deployment, self::Development],
            self::Deployment => [self::Completed, self::Testing],
            self::Completed => [self::UnderReview],
            self::Rejected => [self::UnderReview],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
