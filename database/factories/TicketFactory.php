<?php

namespace Database\Factories;

use App\Enums\TicketImpact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketNumberService;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $numberService = app(TicketNumberService::class);

        return [
            'ticket_number' => $numberService->generateNextTicketNumber(),
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
            'category_id' => TicketCategory::factory(),
            'assigned_to' => null,
            'type' => fake()->randomElement([TicketType::Incident, TicketType::ServiceRequest]),
            'subject' => fake()->sentence(5),
            'description' => fake()->paragraph(3),
            'priority' => TicketPriority::Medium,
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
            'status' => TicketStatus::Open,
            'resolution_notes' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }
}
