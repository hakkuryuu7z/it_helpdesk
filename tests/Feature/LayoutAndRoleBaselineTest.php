<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LayoutAndRoleBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_enterprise_layout_for_authenticated_user(): void
    {
        $role = Role::firstOrCreate(['name' => UserRole::RegularUser->value]);
        $user = User::factory()->create([
            'name' => 'John Doe Employee',
            'email' => 'john.doe@company.test',
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Employee Service Portal');
        $response->assertSee('IT HELPDESK');
        $response->assertSee('Total Submitted');
        $response->assertSee('In Progress / Open');
        $response->assertSee('John Doe Employee');
        // Regular user must not see administration menus
        $response->assertDontSee('Administration');
        $response->assertDontSee('User Management');
    }

    public function test_super_admin_sees_administration_menu_in_sidebar(): void
    {
        $role = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value]);
        $admin = User::factory()->create([
            'name' => 'Admin Boss',
            'email' => 'admin@company.test',
        ]);
        $admin->assignRole($role);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Administration');
        $response->assertSee('Branches');
        $response->assertSee('Departments');
        $response->assertSee('User Management');
    }

    public function test_user_can_be_assigned_any_of_the_five_system_roles(): void
    {
        $roles = [
            UserRole::SuperAdmin,
            UserRole::CentralEdp,
            UserRole::BranchEdp,
            UserRole::BranchAdmin,
            UserRole::RegularUser,
        ];

        foreach ($roles as $enumRole) {
            $roleModel = Role::firstOrCreate(['name' => $enumRole->value]);
            $user = User::factory()->create();
            $user->assignRole($roleModel);

            $this->assertTrue($user->hasRole($enumRole->value));
            $this->assertEquals($enumRole->label(), $enumRole->label());
        }
    }
}
