<?php

namespace Tests\Feature\Notifications;

use App\Actions\Tickets\AssignTicketAction;
use App\Actions\Tickets\CreateTicketAction;
use App\Enums\TicketImpact;
use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TicketCategory;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Services\WhatsAppGatewayService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $centralEdp;
    protected User $branchEdp;
    protected User $userBdg;
    protected Branch $branchBdg;
    protected TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->branchBdg = Branch::create(['name' => 'Bandung Branch', 'code' => 'BDG', 'is_active' => true]);
        $dept = Department::create(['branch_id' => $this->branchBdg->id, 'name' => 'IT Operations']);

        $this->centralEdp = User::factory()->create([
            'branch_id' => $this->branchBdg->id,
            'department_id' => $dept->id,
            'phone' => '+62 811-0000-02',
            'is_active' => true,
        ]);
        $this->centralEdp->assignRole(UserRole::CentralEdp->value);

        $this->branchEdp = User::factory()->create([
            'branch_id' => $this->branchBdg->id,
            'department_id' => $dept->id,
            'phone' => '0811000003',
            'is_active' => true,
        ]);
        $this->branchEdp->assignRole(UserRole::BranchEdp->value);
        $this->branchEdp->assignedBranches()->attach($this->branchBdg->id);

        $this->userBdg = User::factory()->create([
            'branch_id' => $this->branchBdg->id,
            'department_id' => $dept->id,
            'phone' => '081299998888',
            'is_active' => true,
        ]);
        $this->userBdg->assignRole(UserRole::RegularUser->value);

        $this->category = TicketCategory::create(['name' => 'Hardware Issues', 'is_active' => true]);
    }

    public function test_phone_number_normalization(): void
    {
        $gateway = app(WhatsAppGatewayService::class);

        $this->assertEquals('6281234567890', $gateway->normalizePhone('0812-3456-7890'));
        $this->assertEquals('62811000001', $gateway->normalizePhone('+62 811-0000-01'));
        $this->assertEquals('6281399998888', $gateway->normalizePhone('6281399998888'));
        $this->assertEquals('62811000003', $this->branchEdp->routeNotificationForWhatsapp());
        $this->assertEquals('62811000002', $this->centralEdp->routeNotificationForWhatsapp());
    }

    public function test_ticket_creation_notifies_branch_edp(): void
    {
        Notification::fake();

        $action = app(CreateTicketAction::class);
        $ticket = $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'Barcode scanner not scanning',
            'description' => 'Laser light not activating on USB trigger',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        // Branch EDP must be notified
        Notification::assertSentTo(
            $this->branchEdp,
            TicketCreatedNotification::class,
            function ($notification) use ($ticket) {
                $waMessage = $notification->toWhatsApp($this->branchEdp);
                $this->assertStringContainsString($ticket->ticket_number, $waMessage);
                $this->assertStringContainsString('BDG', $waMessage);
                $this->assertStringContainsString('Barcode scanner not scanning', $waMessage);
                return true;
            }
        );
    }

    public function test_high_priority_ticket_escalates_alert_to_central_edp(): void
    {
        Notification::fake();

        $action = app(CreateTicketAction::class);
        $ticket = $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'Main ERP Database Unreachable',
            'description' => 'Connection timeout on port 5432 across the entire branch',
            'impact' => TicketImpact::High,
            'urgency' => TicketUrgency::High,
        ], $this->userBdg);

        // Both Branch EDP and Central EDP must receive alert for High priority
        Notification::assertSentTo($this->branchEdp, TicketCreatedNotification::class);
        Notification::assertSentTo($this->centralEdp, TicketCreatedNotification::class);
    }

    public function test_ticket_assignment_notifies_technician(): void
    {
        Notification::fake();

        $createAction = app(CreateTicketAction::class);
        $ticket = $createAction->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'Billing terminal reboot loop',
            'description' => 'System blue screen during bootup',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        $assignAction = app(AssignTicketAction::class);
        $assignAction->execute($ticket, $this->branchEdp, $this->centralEdp, 'Please replace RAM module');

        Notification::assertSentTo(
            $this->branchEdp,
            TicketAssignedNotification::class,
            function ($notification) use ($ticket) {
                $waMessage = $notification->toWhatsApp($this->branchEdp);
                $this->assertStringContainsString($ticket->ticket_number, $waMessage);
                $this->assertStringContainsString('TICKET ASSIGNMENT', $waMessage);
                $this->assertStringContainsString($this->centralEdp->name, $waMessage);
                return true;
            }
        );
    }

    public function test_baileys_gateway_http_dispatch(): void
    {
        config(['services.whatsapp.driver' => 'baileys']);
        config(['services.whatsapp.baileys_url' => 'http://127.0.0.1:3000']);

        Http::fake([
            'http://127.0.0.1:3000/send-message' => Http::response([
                'success' => true,
                'message' => 'WhatsApp notification sent successfully.',
                'recipient' => '62811000003@s.whatsapp.net',
            ], 200),
        ]);

        $gateway = app(WhatsAppGatewayService::class);
        $result = $gateway->send('0811000003', 'Test message to technician');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:3000/send-message'
                && $request['phone'] === '62811000003'
                && $request['message'] === 'Test message to technician';
        });
    }
}
