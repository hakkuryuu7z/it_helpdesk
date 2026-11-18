<?php

namespace App\Enums;

enum TicketUrgency: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low (Work can proceed with workaround)',
            self::Medium => 'Medium (Work impaired but not halted)',
            self::High => 'High (Operational work completely blocked)',
        };
    }
}
