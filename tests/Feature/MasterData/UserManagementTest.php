<?php

namespace Tests\Feature\MasterData;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_create_user_with_role(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $branch = Branch::factory()->create();
        $dept = Department::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'Michael Scott',
            'email' => 'michael@company.test',
            'username' => 'mscott',
            'phone' => '+628123456789',
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'role' => UserRole::BranchAdmin->value,
            'password' => 'SecurePass123!',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('users.index'));

        $newUser = User::where('email', 'michael@company.test')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole(UserRole::BranchAdmin->value));
    }

    public function test_super_admin_can_assign_multiple_branches_to_branch_edp(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $branchA = Branch::factory()->create(['name' => 'Branch A']);
        $branchB = Branch::factory()->create(['name' => 'Branch B']);

        $response = $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'Dwight Schrute',
            'email' => 'dwight.edp@company.test',
            'branch_id' => $branchA->id,
            'role' => UserRole::BranchEdp->value,
            'password' => 'SecurePass123!',
            'is_active' => true,
            'assigned_branches' => [$branchA->id, $branchB->id],
        ]);

        $response->assertRedirect(route('users.index'));

        $newUser = User::where('email', 'dwight.edp@company.test')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole(UserRole::BranchEdp->value));

        // Verify explicit many-to-many assignments
        $this->assertDatabaseHas('branch_edp_assignments', [
            'user_id' => $newUser->id,
            'branch_id' => $branchA->id,
        ]);
        $this->assertDatabaseHas('branch_edp_assignments', [
            'user_id' => $newUser->id,
            'branch_id' => $branchB->id,
        ]);

        // Helper verification
        $this->assertTrue($newUser->canServiceBranch($branchA->id));
        $this->assertTrue($newUser->canServiceBranch($branchB->id));
    }

    public function test_branch_admin_only_sees_users_in_own_branch(): void
    {
        $branch1 = Branch::factory()->create(['name' => 'Bandung']);
        $branch2 = Branch::factory()->create(['name' => 'Surabaya']);

        $branchAdmin = User::factory()->create(['branch_id' => $branch1->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);

        $user1 = User::factory()->create(['name' => 'Bandung Worker', 'branch_id' => $branch1->id]);
        $user1->assignRole(UserRole::RegularUser->value);

        $user2 = User::factory()->create(['name' => 'Surabaya Worker', 'branch_id' => $branch2->id]);
        $user2->assignRole(UserRole::RegularUser->value);

        $response = $this->actingAs($branchAdmin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Bandung Worker');
        $response->assertDontSee('Surabaya Worker');
    }

    public function test_branch_admin_cannot_elevate_new_user_to_super_admin(): void
    {
        $branch1 = Branch::factory()->create(['name' => 'Bandung']);
        $branch2 = Branch::factory()->create(['name' => 'Surabaya']);

        $branchAdmin = User::factory()->create(['branch_id' => $branch1->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);

        // Branch Admin attempts to submit role=super_admin and branch_id=branch2
        $response = $this->actingAs($branchAdmin)->post(route('users.store'), [
            'name' => 'Sneaky User',
            'email' => 'sneaky@company.test',
            'branch_id' => $branch2->id,
            'role' => UserRole::SuperAdmin->value,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('users.index'));

        $created = User::where('email', 'sneaky@company.test')->first();
        $this->assertNotNull($created);
        // Must be forced to own branch and regular_user
        $this->assertEquals($branch1->id, $created->branch_id);
        $this->assertTrue($created->hasRole(UserRole::RegularUser->value));
        $this->assertFalse($created->hasRole(UserRole::SuperAdmin->value));
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::RegularUser->value);

        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertForbidden();

        $responseCreate = $this->actingAs($user)->get(route('users.create'));
        $responseCreate->assertForbidden();
    }
}
