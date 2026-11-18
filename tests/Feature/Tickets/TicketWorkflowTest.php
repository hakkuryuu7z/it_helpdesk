<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_edp_can_transition_ticket_from_open_to_in_progress(): void
    {
        $branch = Branch::factory()->create();
        $edp = User::factory()->create(['branch_id' => $branch->id]);
        $edp->assignRole(UserRole::BranchEdp->value);
        $edp->assignedBranches()->attach($branch->id);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'status' => TicketStatus::Open,
        ]);

        $response = $this->actingAs($edp)->patch(route('tickets.status', $ticket), [
            'status' => TicketStatus::InProgress->value,
        ]);

        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(TicketStatus::InProgress, $ticket->status);

        // Verify history logged
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'status_changed',
        ]);
    }

    public function test_marking_ticket_resolved_requires_resolution_notes(): void
    {
        $branch = Branch::factory()->create();
        $edp = User::factory()->create(['branch_id' => $branch->id]);
        $edp->assignRole(UserRole::BranchEdp->value);
        $edp->assignedBranches()->attach($branch->id);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'status' => TicketStatus::InProgress,
        ]);

        // Attempt without resolution notes
        $response = $this->actingAs($edp)->patch(route('tickets.status', $ticket), [
            'status' => TicketStatus::Resolved->value,
            'resolution_notes' => '',
        ]);

        $response->assertSessionHasErrors('resolution_notes');
        $this->assertEquals(TicketStatus::InProgress, $ticket->fresh()->status);

        // Provide resolution notes
        $responseSuccess = $this->actingAs($edp)->patch(route('tickets.status', $ticket), [
            'status' => TicketStatus::Resolved->value,
            'resolution_notes' => 'Replaced network switch port and tested packet latency.',
        ]);

        $responseSuccess->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(TicketStatus::Resolved, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertStringContainsString('Replaced network switch port', $ticket->resolution_notes);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        $edp = User::factory()->create(['branch_id' => $branch->id]);
        $edp->assignRole(UserRole::BranchEdp->value);
        $edp->assignedBranches()->attach($branch->id);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'status' => TicketStatus::Open,
        ]);

        // Direct Open -> Closed is invalid workflow
        $response = $this->actingAs($edp)->patch(route('tickets.status', $ticket), [
            'status' => TicketStatus::Closed->value,
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_ticket_assignment_preserves_history(): void
    {
        $branch = Branch::factory()->create();
        $centralEdp = User::factory()->create();
        $centralEdp->assignRole(UserRole::CentralEdp->value);

        $technician = User::factory()->create(['branch_id' => $branch->id]);
        $technician->assignRole(UserRole::BranchEdp->value);
        $technician->assignedBranches()->attach($branch->id);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($centralEdp)->post(route('tickets.assign', $ticket), [
            'assigned_to' => $technician->id,
            'notes' => 'Assigned for immediate on-site inspection.',
        ]);

        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals($technician->id, $ticket->assigned_to);

        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'assigned_to' => $technician->id,
            'assigned_by' => $centralEdp->id,
            'notes' => 'Assigned for immediate on-site inspection.',
        ]);
    }

    public function test_regular_user_can_confirm_resolution(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole(UserRole::RegularUser->value);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'status' => TicketStatus::Resolved,
            'resolution_notes' => 'Fixed driver error.',
        ]);

        $response = $this->actingAs($user)->patch(route('tickets.status', $ticket), [
            'status' => TicketStatus::Closed->value,
        ]);

        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(TicketStatus::Closed, $ticket->status);
        $this->assertNotNull($ticket->closed_at);
    }
}
