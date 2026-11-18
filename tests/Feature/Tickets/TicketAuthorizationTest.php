<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_regular_user_can_only_see_their_own_tickets_in_index(): void
    {
        $branch = Branch::factory()->create();

        $userA = User::factory()->create(['branch_id' => $branch->id]);
        $userA->assignRole(UserRole::RegularUser->value);

        $userB = User::factory()->create(['branch_id' => $branch->id]);
        $userB->assignRole(UserRole::RegularUser->value);

        $ticketA = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $userA->id,
            'subject' => 'Ticket from User A',
        ]);

        $ticketB = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $userB->id,
            'subject' => 'Ticket from User B',
        ]);

        $response = $this->actingAs($userA)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSee('Ticket from User A');
        $response->assertDontSee('Ticket from User B');
    }

    public function test_regular_user_cannot_view_another_users_ticket_detail(): void
    {
        $branch = Branch::factory()->create();

        $userA = User::factory()->create(['branch_id' => $branch->id]);
        $userA->assignRole(UserRole::RegularUser->value);

        $userB = User::factory()->create(['branch_id' => $branch->id]);
        $userB->assignRole(UserRole::RegularUser->value);

        $ticketB = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $userB->id,
        ]);

        $response = $this->actingAs($userA)->get(route('tickets.show', $ticketB));

        $response->assertForbidden();
    }

    public function test_branch_edp_can_only_access_assigned_branches(): void
    {
        $branchBandung = Branch::factory()->create(['code' => 'BDG', 'name' => 'Bandung']);
        $branchSurabaya = Branch::factory()->create(['code' => 'SBY', 'name' => 'Surabaya']);

        $edpBandung = User::factory()->create(['branch_id' => $branchBandung->id]);
        $edpBandung->assignRole(UserRole::BranchEdp->value);
        $edpBandung->assignedBranches()->attach($branchBandung->id);

        $ticketBandung = Ticket::factory()->create(['branch_id' => $branchBandung->id]);
        $ticketSurabaya = Ticket::factory()->create(['branch_id' => $branchSurabaya->id]);

        // Access assigned branch
        $response1 = $this->actingAs($edpBandung)->get(route('tickets.show', $ticketBandung));
        $response1->assertOk();

        // Access unassigned branch
        $response2 = $this->actingAs($edpBandung)->get(route('tickets.show', $ticketSurabaya));
        $response2->assertForbidden();
    }

    public function test_central_edp_can_access_tickets_across_all_branches(): void
    {
        $branchBandung = Branch::factory()->create(['code' => 'BDG']);
        $branchSurabaya = Branch::factory()->create(['code' => 'SBY']);

        $centralEdp = User::factory()->create();
        $centralEdp->assignRole(UserRole::CentralEdp->value);

        $ticketBandung = Ticket::factory()->create(['branch_id' => $branchBandung->id]);
        $ticketSurabaya = Ticket::factory()->create(['branch_id' => $branchSurabaya->id]);

        $this->actingAs($centralEdp)->get(route('tickets.show', $ticketBandung))->assertOk();
        $this->actingAs($centralEdp)->get(route('tickets.show', $ticketSurabaya))->assertOk();
    }

    public function test_ticket_branch_id_is_stamped_server_side_ignoring_client_spoof(): void
    {
        $branchReal = Branch::factory()->create(['code' => 'REAL']);
        $branchSpoofed = Branch::factory()->create(['code' => 'SPOOF']);

        $user = User::factory()->create(['branch_id' => $branchReal->id]);
        $user->assignRole(UserRole::RegularUser->value);

        $category = TicketCategory::factory()->create();

        // User attempts to inject branch_id of branchSpoofed
        $response = $this->actingAs($user)->post(route('tickets.store'), [
            'type' => TicketType::Incident->value,
            'category_id' => $category->id,
            'branch_id' => $branchSpoofed->id,
            'subject' => 'Printer jammed',
            'description' => 'The billing printer is jamming consistently.',
            'impact' => 'low',
            'urgency' => 'low',
        ]);

        $response->assertRedirect();

        $ticket = Ticket::where('subject', 'Printer jammed')->first();
        $this->assertNotNull($ticket);
        // branch_id must be branchReal, not branchSpoofed!
        $this->assertEquals($branchReal->id, $ticket->branch_id);
        $this->assertEquals($user->id, $ticket->created_by);
    }
}
