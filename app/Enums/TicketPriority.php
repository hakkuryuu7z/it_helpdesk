<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'badge-priority-low',
            self::Medium => 'badge-priority-medium',
            self::High => 'badge-priority-high',
            self::Critical => 'badge-priority-critical',
        };
    }
}
