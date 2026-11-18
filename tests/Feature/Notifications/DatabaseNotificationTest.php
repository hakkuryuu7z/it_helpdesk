<?php

namespace Tests\Feature\Notifications;

use App\Actions\Tickets\AddTicketCommentAction;
use App\Actions\Tickets\AssignTicketAction;
use App\Actions\Tickets\CreateTicketAction;
use App\Actions\Tickets\TransitionTicketStatusAction;
use App\Enums\TicketImpact;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $centralEdp;
    protected User $branchEdp;
    protected User $regularUser;
    protected Branch $branch;
    protected TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->branch = Branch::create(['name' => 'Bandung Branch', 'code' => 'BDG', 'is_active' => true]);
        $dept = Department::create(['branch_id' => $this->branch->id, 'name' => 'IT Operations']);

        $this->centralEdp = User::factory()->create(['branch_id' => $this->branch->id, 'department_id' => $dept->id, 'is_active' => true]);
        $this->centralEdp->assignRole(UserRole::CentralEdp->value);

        $this->branchEdp = User::factory()->create(['branch_id' => $this->branch->id, 'department_id' => $dept->id, 'is_active' => true]);
        $this->branchEdp->assignRole(UserRole::BranchEdp->value);
        $this->branchEdp->assignedBranches()->attach($this->branch->id);

        $this->regularUser = User::factory()->create(['branch_id' => $this->branch->id, 'department_id' => $dept->id, 'is_active' => true]);
        $this->regularUser->assignRole(UserRole::RegularUser->value);

        $this->category = TicketCategory::create(['name' => 'Network', 'is_active' => true]);
    }

    public function test_assigning_ticket_notifies_technician(): void
    {
        $createAction = app(CreateTicketAction::class);
        $ticket = $createAction->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'VPN Tunnel Dropped',
            'description' => 'Cannot access main database via VPN',
            'impact' => TicketImpact::High,
            'urgency' => TicketUrgency::High,
        ], $this->regularUser);

        // Branch EDP receives automatic creation alert
        $this->assertEquals(1, $this->branchEdp->notifications()->count());

        $assignAction = app(AssignTicketAction::class);
        $assignAction->execute($ticket, $this->branchEdp, $this->centralEdp, 'Please investigate ASAP');

        // Branch EDP receives assignment alert (total 2)
        $this->assertEquals(2, $this->branchEdp->fresh()->notifications()->count());
        $notification = $this->branchEdp->fresh()->notifications->firstWhere('data.type', 'ticket_assigned');
        $this->assertNotNull($notification);
        $this->assertEquals('ticket_assigned', $notification->data['type']);
        $this->assertStringContainsString($ticket->ticket_number, $notification->data['title']);
    }

    public function test_ticket_status_change_notifies_creator(): void
    {
        $createAction = app(CreateTicketAction::class);
        $ticket = $createAction->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'Outlook not receiving emails',
            'description' => 'Mailbox full notification popup',
            'impact' => TicketImpact::Medium,
            'urgency' => TicketUrgency::Medium,
        ], $this->regularUser);

        $this->assertEquals(0, $this->regularUser->notifications()->count());

        $transitionAction = app(TransitionTicketStatusAction::class);
        $transitionAction->execute($ticket, TicketStatus::InProgress, $this->branchEdp);

        $this->assertEquals(1, $this->regularUser->fresh()->notifications()->count());
        $notification = $this->regularUser->notifications()->first();
        $this->assertEquals('status_changed', $notification->data['type']);
        $this->assertStringContainsString('In Progress', $notification->data['message']);
    }

    public function test_internal_note_never_notifies_regular_user(): void
    {
        $createAction = app(CreateTicketAction::class);
        $ticket = $createAction->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'Suspicious USB Activity',
            'description' => 'Security team review required',
            'impact' => TicketImpact::High,
            'urgency' => TicketUrgency::High,
        ], $this->regularUser);

        // Assign to branch EDP
        $ticket->update(['assigned_to' => $this->branchEdp->id]);

        $commentAction = app(AddTicketCommentAction::class);

        // Central EDP adds internal note
        $commentAction->execute($ticket, $this->centralEdp, 'Investigating threat actor IP signature', true);

        // Regular user must NOT receive notification of internal notes
        $this->assertEquals(0, $this->regularUser->fresh()->notifications()->count());

        // Assigned technician receives creation + internal note notifications (total 2)
        $this->assertEquals(2, $this->branchEdp->fresh()->notifications()->count());
    }

    public function test_notification_can_be_marked_as_read_and_redirects(): void
    {
        $createAction = app(CreateTicketAction::class);
        $ticket = $createAction->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'Printer Toner Low',
            'description' => 'Replace black toner cartridge',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->regularUser);

        $assignAction = app(AssignTicketAction::class);
        $assignAction->execute($ticket, $this->branchEdp, $this->centralEdp);

        $notif = $this->branchEdp->notifications()->first();
        $this->assertNull($notif->read_at);

        $response = $this->actingAs($this->branchEdp)->post(route('notifications.read', $notif->id));

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertNotNull($notif->fresh()->read_at);
    }
}
