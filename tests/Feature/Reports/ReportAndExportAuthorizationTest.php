<?php

namespace Tests\Feature\Reports;

use App\Actions\Tickets\CreateTicketAction;
use App\Enums\TicketImpact;
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

class ReportAndExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $centralEdp;
    protected User $branchEdp;
    protected User $userBdg;
    protected User $userSby;
    protected Branch $branchBdg;
    protected Branch $branchSby;
    protected TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->branchBdg = Branch::create(['name' => 'Bandung Branch', 'code' => 'BDG', 'is_active' => true]);
        $this->branchSby = Branch::create(['name' => 'Surabaya Branch', 'code' => 'SBY', 'is_active' => true]);

        $deptBdg = Department::create(['branch_id' => $this->branchBdg->id, 'name' => 'IT Operations BDG']);
        $deptSby = Department::create(['branch_id' => $this->branchSby->id, 'name' => 'IT Operations SBY']);

        $this->centralEdp = User::factory()->create(['branch_id' => $this->branchBdg->id, 'department_id' => $deptBdg->id, 'is_active' => true]);
        $this->centralEdp->assignRole(UserRole::CentralEdp->value);

        $this->branchEdp = User::factory()->create(['branch_id' => $this->branchBdg->id, 'department_id' => $deptBdg->id, 'is_active' => true]);
        $this->branchEdp->assignRole(UserRole::BranchEdp->value);
        $this->branchEdp->assignedBranches()->attach($this->branchBdg->id);

        $this->userBdg = User::factory()->create(['branch_id' => $this->branchBdg->id, 'department_id' => $deptBdg->id, 'is_active' => true]);
        $this->userBdg->assignRole(UserRole::RegularUser->value);

        $this->userSby = User::factory()->create(['branch_id' => $this->branchSby->id, 'department_id' => $deptSby->id, 'is_active' => true]);
        $this->userSby->assignRole(UserRole::RegularUser->value);

        $this->category = TicketCategory::create(['name' => 'Network', 'is_active' => true]);
    }

    public function test_regular_user_report_only_includes_own_tickets(): void
    {
        $action = app(CreateTicketAction::class);
        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'BDG Switch Port Down',
            'description' => 'Port 14 is flashing amber',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'SBY Server Offline',
            'description' => 'SBY Branch local server unpingable',
            'impact' => TicketImpact::High,
            'urgency' => TicketUrgency::High,
        ], $this->userSby);

        $response = $this->actingAs($this->userBdg)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('BDG Switch Port Down');
        $response->assertDontSee('SBY Server Offline');
    }

    public function test_branch_edp_report_only_includes_assigned_branch(): void
    {
        $action = app(CreateTicketAction::class);
        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'BDG Router Reboot',
            'description' => 'Gateway router rebooted unexpectedly',
            'impact' => TicketImpact::Medium,
            'urgency' => TicketUrgency::Medium,
        ], $this->userBdg);

        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'SBY Core Switch Failed',
            'description' => 'Power supply unit 2 blown',
            'impact' => TicketImpact::High,
            'urgency' => TicketUrgency::High,
        ], $this->userSby);

        $response = $this->actingAs($this->branchEdp)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('BDG Router Reboot');
        $response->assertDontSee('SBY Core Switch Failed');
    }

    public function test_csv_export_respects_branch_boundaries_and_streams_headers(): void
    {
        $action = app(CreateTicketAction::class);
        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'BDG Firewall Policy Request',
            'description' => 'Open port 443 for warehouse terminal',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'SBY Confidential Finance Incident',
            'description' => 'Finance data access audit',
            'impact' => TicketImpact::High,
            'urgency' => TicketUrgency::High,
        ], $this->userSby);

        // Branch EDP exports CSV
        $response = $this->actingAs($this->branchEdp)->get(route('reports.export-csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');

        // Capture streamed response content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('BDG Firewall Policy Request', $content);
        $this->assertStringNotContainsString('SBY Confidential Finance Incident', $content);
        $this->assertStringContainsString('"Ticket Number","Branch Code","Branch Name"', $content);
    }

    public function test_csv_export_escapes_formula_injection_attempts(): void
    {
        $action = app(CreateTicketAction::class);
        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => '=1+1;cmd|calc.exe', // Malicious CSV injection pattern
            'description' => 'Testing CSV sanitization',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        $response = $this->actingAs($this->centralEdp)->get(route('reports.export-csv'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // The dangerous leading '=' must be prepended with a single quote "'"
        $this->assertStringContainsString("'=1+1;cmd|calc.exe", $content);
    }
}
