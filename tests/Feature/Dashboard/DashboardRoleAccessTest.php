<?php

namespace Tests\Feature\Dashboard;

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
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $centralEdp;
    protected User $branchEdp;
    protected User $regularUser;
    protected Branch $branchBdg;
    protected Branch $branchSby;
    protected TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->branchBdg = Branch::create(['name' => 'Bandung Branch', 'code' => 'BDG', 'is_active' => true]);
        $this->branchSby = Branch::create(['name' => 'Surabaya Branch', 'code' => 'SBY', 'is_active' => true]);

        $dept = Department::create(['branch_id' => $this->branchBdg->id, 'name' => 'IT Operations']);

        $this->superAdmin = User::factory()->create(['branch_id' => $this->branchBdg->id, 'department_id' => $dept->id, 'is_active' => true]);
        $this->superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->centralEdp = User::factory()->create(['branch_id' => $this->branchBdg->id, 'department_id' => $dept->id, 'is_active' => true]);
        $this->centralEdp->assignRole(UserRole::CentralEdp->value);

        $this->branchEdp = User::factory()->create(['branch_id' => $this->branchBdg->id, 'department_id' => $dept->id, 'is_active' => true]);
        $this->branchEdp->assignRole(UserRole::BranchEdp->value);
        $this->branchEdp->assignedBranches()->attach($this->branchBdg->id);

        $this->regularUser = User::factory()->create(['branch_id' => $this->branchBdg->id, 'department_id' => $dept->id, 'is_active' => true]);
        $this->regularUser->assignRole(UserRole::RegularUser->value);

        $this->category = TicketCategory::create(['name' => 'Hardware Support', 'is_active' => true]);
    }

    public function test_regular_user_sees_employee_service_portal(): void
    {
        $action = app(CreateTicketAction::class);
        $action->execute([
            'type' => TicketType::Incident,
            'category_id' => $this->category->id,
            'subject' => 'Printer jammed in accounting',
            'description' => 'Tray 2 paper jam error 501',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->regularUser);

        $response = $this->actingAs($this->regularUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Employee Service Portal');
        $response->assertSee('Printer jammed in accounting');
        $response->assertSee('Total Submitted');
    }

    public function test_branch_edp_sees_technical_workbench_for_assigned_branch(): void
    {
        $response = $this->actingAs($this->branchEdp)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Branch EDP Technical Workbench');
        $response->assertSee('BDG');
        $response->assertSee('Assigned To Me');
        $response->assertSee('Unassigned Queue');
    }

    public function test_central_edp_sees_cross_branch_operations_center(): void
    {
        $response = $this->actingAs($this->centralEdp)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Central EDP Operations Center');
        $response->assertSee('System-Wide Tickets');
        $response->assertSee('Ticket Volume by Branch');
    }

    public function test_super_admin_sees_control_center(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Super Admin Control Center');
        $response->assertSee('Recent System Audit Activity');
        $response->assertSee('Tickets by Status Distribution');
    }
}
