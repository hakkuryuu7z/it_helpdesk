<?php

namespace Tests\Feature\Admin;

use App\Actions\Tickets\CreateTicketAction;
use App\Enums\TicketImpact;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TicketCategory;
use App\Models\User;
use App\Models\WhatsAppLog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppGatewayAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branchEdp;
    protected User $regularUser;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $dept = Department::factory()->create(['branch_id' => $this->branch->id]);

        $this->superAdmin = User::factory()->create(['branch_id' => $this->branch->id, 'department_id' => $dept->id]);
        $this->superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->branchEdp = User::factory()->create(['branch_id' => $this->branch->id, 'department_id' => $dept->id]);
        $this->branchEdp->assignRole(UserRole::BranchEdp->value);

        $this->regularUser = User::factory()->create(['branch_id' => $this->branch->id, 'department_id' => $dept->id]);
        $this->regularUser->assignRole(UserRole::RegularUser->value);
    }

    public function test_super_admin_can_access_whatsapp_gateway_monitoring_page(): void
    {
        Http::fake([
            '*/qr-data' => Http::response([
                'success' => true,
                'connected' => true,
                'user' => '628123456789:1@s.whatsapp.net',
                'has_qr_pending' => false,
                'qr_data_url' => null,
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.whatsapp.index'));

        $response->assertOk();
        $response->assertSee('WhatsApp Gateway & Audit Logs', false);
        $response->assertSee('Perangkat WhatsApp Gateway');
        $response->assertSee('628123456789');
    }

    public function test_regular_user_cannot_access_whatsapp_gateway_monitoring(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.whatsapp.index'));

        $response->assertForbidden();
    }

    public function test_branch_edp_cannot_access_whatsapp_gateway_monitoring(): void
    {
        $response = $this->actingAs($this->branchEdp)->get(route('admin.whatsapp.index'));

        $response->assertForbidden();
    }

    public function test_status_endpoint_returns_json_for_ajax_polling(): void
    {
        Http::fake([
            '*/qr-data' => Http::response([
                'success' => true,
                'connected' => false,
                'user' => null,
                'has_qr_pending' => true,
                'qr_data_url' => 'data:image/png;base64,fakeqrdata',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.whatsapp.status'));

        $response->assertOk();
        $response->assertJson([
            'connected' => false,
            'has_qr_pending' => true,
        ]);
    }

    public function test_super_admin_can_send_test_message_and_creates_log(): void
    {
        Http::fake([
            '*/send-message' => Http::response([
                'success' => true,
                'message' => 'Sent',
                'recipient' => '628999888777@s.whatsapp.net',
                'messageId' => 'MSG-12345',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.whatsapp.send-test'), [
            'phone' => '08999888777',
            'message' => 'Halo ini adalah tes pesan WhatsApp.',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'recipient_phone' => '628999888777',
            'status' => 'sent',
        ]);
    }

    public function test_ticket_creation_records_whatsapp_notification_log(): void
    {
        Http::fake([
            '*/send-message' => Http::response(['success' => true], 200),
        ]);

        $this->branchEdp->phone = '081234567890';
        $this->branchEdp->save();
        $this->branchEdp->assignedBranches()->attach($this->branch->id);

        $category = TicketCategory::factory()->create();

        $action = app(CreateTicketAction::class);
        $ticket = $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $category->id,
            'subject' => 'Printer kasir error',
            'description' => 'Macet tidak bisa print struk',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->regularUser);

        $this->assertDatabaseHas('whatsapp_logs', [
            'ticket_id' => $ticket->id,
            'recipient_phone' => '6281234567890',
            'status' => 'sent',
        ]);
    }

    public function test_super_admin_can_clear_whatsapp_logs(): void
    {
        WhatsAppLog::create([
            'recipient_phone' => '6281234567890',
            'message' => 'Old message log',
            'status' => 'sent',
        ]);

        $this->assertDatabaseCount('whatsapp_logs', 1);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.whatsapp.clear-logs'));

        $response->assertRedirect();
        $this->assertDatabaseCount('whatsapp_logs', 0);
    }
}
