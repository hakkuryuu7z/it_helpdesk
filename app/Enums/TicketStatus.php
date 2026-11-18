<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case InProgress = 'in_progress';
    case WaitingUser = 'waiting_user';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::UnderReview => 'Under Review',
            self::InProgress => 'In Progress',
            self::WaitingUser => 'Waiting User Response',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'badge-status-open',
            self::UnderReview => 'badge-status-under_review',
            self::InProgress => 'badge-status-in_progress',
            self::WaitingUser => 'badge-status-waiting_user',
            self::Resolved => 'badge-status-resolved',
            self::Closed => 'badge-status-closed',
            self::Rejected => 'badge-status-rejected',
        };
    }
}
