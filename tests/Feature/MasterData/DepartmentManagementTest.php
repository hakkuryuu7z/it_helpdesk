<?php

namespace Tests\Feature\MasterData;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_create_department_for_any_branch(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $branch = Branch::factory()->create(['code' => 'BDG', 'name' => 'Branch Bandung']);

        $response = $this->actingAs($superAdmin)->post(route('departments.store'), [
            'branch_id' => $branch->id,
            'name' => 'IT Support',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', [
            'branch_id' => $branch->id,
            'name' => 'IT Support',
        ]);
    }

    public function test_duplicate_department_name_in_same_branch_is_rejected(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $branch = Branch::factory()->create();
        Department::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'Finance',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('departments.store'), [
            'branch_id' => $branch->id,
            'name' => 'Finance',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_branch_admin_is_restricted_to_creating_department_in_own_branch(): void
    {
        $branch1 = Branch::factory()->create(['name' => 'Bandung']);
        $branch2 = Branch::factory()->create(['name' => 'Surabaya']);

        $branchAdmin = User::factory()->create(['branch_id' => $branch1->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);

        // Attempt to create in branch 2
        $response = $this->actingAs($branchAdmin)->post(route('departments.store'), [
            'branch_id' => $branch2->id,
            'name' => 'Logistics Unit',
        ]);

        $response->assertRedirect(route('departments.index'));

        // Backend must have forced branch_id to branch1, ignoring client attempt for branch2
        $this->assertDatabaseHas('departments', [
            'branch_id' => $branch1->id,
            'name' => 'Logistics Unit',
        ]);
        $this->assertDatabaseMissing('departments', [
            'branch_id' => $branch2->id,
            'name' => 'Logistics Unit',
        ]);
    }

    public function test_regular_user_cannot_create_department(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::RegularUser->value);

        $response = $this->actingAs($user)->post(route('departments.store'), [
            'branch_id' => 1,
            'name' => 'Hacking Dept',
        ]);

        $response->assertForbidden();
    }
}
