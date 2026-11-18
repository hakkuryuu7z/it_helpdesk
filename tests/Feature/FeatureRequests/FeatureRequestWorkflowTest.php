<?php

namespace Tests\Feature\FeatureRequests;

use App\Actions\Tickets\CreateTicketAction;
use App\Enums\FeatureRequestStage;
use App\Enums\TicketImpact;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
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

        $deptBdg = Department::create(['branch_id' => $this->branchBdg->id, 'name' => 'Finance BDG']);
        $deptSby = Department::create(['branch_id' => $this->branchSby->id, 'name' => 'Finance SBY']);

        $this->superAdmin = User::factory()->create([
            'branch_id' => $this->branchBdg->id,
            'department_id' => $deptBdg->id,
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->centralEdp = User::factory()->create([
            'branch_id' => $this->branchBdg->id,
            'department_id' => $deptBdg->id,
            'is_active' => true,
        ]);
        $this->centralEdp->assignRole(UserRole::CentralEdp->value);

        $this->branchEdp = User::factory()->create([
            'branch_id' => $this->branchBdg->id,
            'department_id' => $deptBdg->id,
            'is_active' => true,
        ]);
        $this->branchEdp->assignRole(UserRole::BranchEdp->value);
        $this->branchEdp->assignedBranches()->attach($this->branchBdg->id);

        $this->userBdg = User::factory()->create([
            'branch_id' => $this->branchBdg->id,
            'department_id' => $deptBdg->id,
            'is_active' => true,
        ]);
        $this->userBdg->assignRole(UserRole::RegularUser->value);

        $this->userSby = User::factory()->create([
            'branch_id' => $this->branchSby->id,
            'department_id' => $deptSby->id,
            'is_active' => true,
        ]);
        $this->userSby->assignRole(UserRole::RegularUser->value);

        $this->category = TicketCategory::create([
            'name' => 'ERP Enhancements',
            'is_active' => true,
        ]);
    }

    public function test_submitting_feature_request_ticket_automatically_creates_feature_record(): void
    {
        $response = $this->actingAs($this->userBdg)->post(route('tickets.store'), [
            'type' => TicketType::FeatureRequest->value,
            'category_id' => $this->category->id,
            'subject' => 'Auto Reconciliation Module',
            'description' => 'Need automated bank reconciliation matching ledger with statement',
            'business_requirements' => 'Match transactions by reference and amount',
            'problem_description' => 'Manual reconciliation takes 3 days every month end',
            'expected_outcome' => 'Reconciliation done in under 1 hour',
            'impact' => TicketImpact::High->value,
            'urgency' => TicketUrgency::Medium->value,
        ]);

        $response->assertRedirect();

        $ticket = Ticket::where('subject', 'Auto Reconciliation Module')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals(TicketType::FeatureRequest, $ticket->type);

        $fr = $ticket->featureRequest;
        $this->assertNotNull($fr);
        $this->assertEquals(FeatureRequestStage::Submitted, $fr->stage);
        $this->assertEquals('Match transactions by reference and amount', $fr->business_requirements);
        $this->assertDatabaseHas('feature_request_activities', [
            'feature_request_id' => $fr->id,
            'to_stage' => FeatureRequestStage::Submitted->value,
        ]);
    }

    public function test_regular_user_cannot_update_feature_request_stage(): void
    {
        $action = app(CreateTicketAction::class);
        $ticket = $action->execute([
            'type' => TicketType::FeatureRequest,
            'category_id' => $this->category->id,
            'subject' => 'Sales Dashboard Export',
            'description' => 'Export sales chart to PowerPoint format',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        $fr = $ticket->featureRequest;

        $response = $this->actingAs($this->userBdg)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Accepted->value,
        ]);

        $response->assertForbidden();
        $this->assertEquals(FeatureRequestStage::Submitted, $fr->fresh()->stage);
    }

    public function test_central_edp_can_transition_feature_through_valid_stages(): void
    {
        $action = app(CreateTicketAction::class);
        $ticket = $action->execute([
            'type' => TicketType::FeatureRequest,
            'category_id' => $this->category->id,
            'subject' => 'Invoice Multi-Currency Support',
            'description' => 'Support USD and EUR invoices',
            'impact' => TicketImpact::High,
            'urgency' => TicketUrgency::High,
        ], $this->userBdg);

        $fr = $ticket->featureRequest;

        // 1. Submitted -> Under Review
        $res1 = $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::UnderReview->value,
            'notes' => 'Reviewing architectural feasibility',
        ]);
        $res1->assertRedirect();
        $this->assertEquals(FeatureRequestStage::UnderReview, $fr->fresh()->stage);

        // 2. Under Review -> Accepted
        $res2 = $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Accepted->value,
            'dev_priority' => 'high',
            'estimated_effort' => '3 Sprints',
            'notes' => 'Approved by Steering Committee',
        ]);
        $res2->assertRedirect();
        $fr->refresh();
        $this->assertEquals(FeatureRequestStage::Accepted, $fr->stage);
        $this->assertEquals('high', $fr->dev_priority);
        $this->assertEquals('3 Sprints', $fr->estimated_effort);

        // 3. Accepted -> Planning
        $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Planning->value,
        ]);
        $this->assertEquals(FeatureRequestStage::Planning, $fr->fresh()->stage);

        // 4. Planning -> Development
        $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Development->value,
            'technical_notes' => 'Using ExchangeRates API and decimal(18,4) currency schema',
        ]);
        $fr->refresh();
        $this->assertEquals(FeatureRequestStage::Development, $fr->stage);
        $this->assertEquals(TicketStatus::InProgress, $fr->ticket->fresh()->status);

        // 5. Development -> Testing
        $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Testing->value,
            'testing_notes' => 'Verify rounding on currency conversions',
        ]);
        $this->assertEquals(FeatureRequestStage::Testing, $fr->fresh()->stage);

        // 6. Testing -> Deployment
        $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Deployment->value,
            'deployment_notes' => 'Release in v2.4 maintenance window',
        ]);
        $this->assertEquals(FeatureRequestStage::Deployment, $fr->fresh()->stage);

        // 7. Deployment -> Completed
        $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Completed->value,
            'notes' => 'Deployed successfully to production cluster',
        ]);
        $fr->refresh();
        $this->assertEquals(FeatureRequestStage::Completed, $fr->stage);
        $this->assertNotNull($fr->completed_at);
        $this->assertEquals(TicketStatus::Resolved, $fr->ticket->fresh()->status);
    }

    public function test_invalid_stage_transition_is_rejected(): void
    {
        $action = app(CreateTicketAction::class);
        $ticket = $action->execute([
            'type' => TicketType::FeatureRequest,
            'category_id' => $this->category->id,
            'subject' => 'Direct Jump Test',
            'description' => 'Test invalid transition',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        $fr = $ticket->featureRequest;

        // Submitted -> Completed directly should fail
        $response = $this->actingAs($this->centralEdp)->patch(route('feature-requests.update-stage', $fr), [
            'stage' => FeatureRequestStage::Completed->value,
        ]);

        $response->assertSessionHasErrors('stage');
        $this->assertEquals(FeatureRequestStage::Submitted, $fr->fresh()->stage);
    }

    public function test_feature_request_index_respects_branch_and_user_boundaries(): void
    {
        $action = app(CreateTicketAction::class);

        // Ticket 1 for User BDG
        $ticket1 = $action->execute([
            'type' => TicketType::FeatureRequest,
            'category_id' => $this->category->id,
            'subject' => 'BDG Specific Feature',
            'description' => 'Feature for BDG branch only',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userBdg);

        // Ticket 2 for User SBY
        $ticket2 = $action->execute([
            'type' => TicketType::FeatureRequest,
            'category_id' => $this->category->id,
            'subject' => 'SBY Specific Feature',
            'description' => 'Feature for SBY branch only',
            'impact' => TicketImpact::Low,
            'urgency' => TicketUrgency::Low,
        ], $this->userSby);

        // 1. Regular User BDG should only see Ticket 1's feature request
        $resUser = $this->actingAs($this->userBdg)->get(route('feature-requests.index'));
        $resUser->assertOk();
        $resUser->assertSee('BDG Specific Feature');
        $resUser->assertDontSee('SBY Specific Feature');

        // 2. Branch EDP (assigned to BDG) should only see Ticket 1
        $resEdp = $this->actingAs($this->branchEdp)->get(route('feature-requests.index'));
        $resEdp->assertOk();
        $resEdp->assertSee('BDG Specific Feature');
        $resEdp->assertDontSee('SBY Specific Feature');

        // 3. Central EDP should see both
        $resCentral = $this->actingAs($this->centralEdp)->get(route('feature-requests.index'));
        $resCentral->assertOk();
        $resCentral->assertSee('BDG Specific Feature');
        $resCentral->assertSee('SBY Specific Feature');
    }
}
