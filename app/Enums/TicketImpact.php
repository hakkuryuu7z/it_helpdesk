<?php

namespace App\Enums;

enum TicketImpact: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low (Individual User)',
            self::Medium => 'Medium (Department / Workgroup)',
            self::High => 'High (Entire Branch / Critical Operation)',
        };
    }
}
