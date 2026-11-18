<?php

namespace Tests\Feature\MasterData;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_view_branches_list(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $branch = Branch::factory()->create(['name' => 'Branch Bandung', 'code' => 'BDG']);

        $response = $this->actingAs($superAdmin)->get(route('branches.index'));

        $response->assertOk();
        $response->assertSee('Branch Bandung');
        $response->assertSee('BDG');
    }

    public function test_super_admin_can_create_new_branch(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $response = $this->actingAs($superAdmin)->post(route('branches.store'), [
            'code' => 'SBY',
            'name' => 'Branch Surabaya',
            'city' => 'Surabaya',
            'address' => 'Jl. Darmo No. 10',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'code' => 'SBY',
            'name' => 'Branch Surabaya',
        ]);
    }

    public function test_branch_code_must_be_unique(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        Branch::factory()->create(['code' => 'HQ', 'name' => 'Head Office']);

        $response = $this->actingAs($superAdmin)->post(route('branches.store'), [
            'code' => 'HQ',
            'name' => 'Duplicate HQ',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_regular_user_cannot_create_branch(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::RegularUser->value);

        $response = $this->actingAs($user)->post(route('branches.store'), [
            'code' => 'TEST',
            'name' => 'Unauthorized Branch',
        ]);

        $response->assertForbidden();
    }

    public function test_branch_edp_sees_only_assigned_branches(): void
    {
        $branch1 = Branch::factory()->create(['code' => 'BDG', 'name' => 'Bandung']);
        $branch2 = Branch::factory()->create(['code' => 'SBY', 'name' => 'Surabaya']);

        $edpUser = User::factory()->create(['branch_id' => $branch1->id]);
        $edpUser->assignRole(UserRole::BranchEdp->value);
        $edpUser->assignedBranches()->attach($branch1->id);

        $response = $this->actingAs($edpUser)->get(route('branches.index'));

        $response->assertOk();
        $response->assertSee('Bandung');
        $response->assertDontSee('Surabaya');
    }
}
