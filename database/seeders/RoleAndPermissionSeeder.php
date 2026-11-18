<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // Branches
            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',

            // Departments
            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.manage_branch_only',

            // Roles & Permissions
            'roles.manage',

            // Tickets
            'tickets.view_all',
            'tickets.view_assigned_branch',
            'tickets.view_own',
            'tickets.create',
            'tickets.assign',
            'tickets.change_priority',
            'tickets.change_status',
            'tickets.resolve',
            'tickets.close',
            'tickets.reopen',

            // Comments & Notes
            'comments.add_public',
            'comments.view_internal',
            'comments.add_internal',

            // Attachments
            'attachments.upload',
            'attachments.download',

            // Feature Requests
            'feature_requests.view',
            'feature_requests.create',
            'feature_requests.manage_stage',

            // Reports
            'reports.view_all',
            'reports.view_assigned_branch',
            'reports.export',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 1. Super Admin Role
        $superAdminRole = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // 2. Central EDP Role
        $centralEdpRole = Role::firstOrCreate(['name' => UserRole::CentralEdp->value, 'guard_name' => 'web']);
        $centralEdpRole->syncPermissions([
            'branches.view',
            'departments.view',
            'users.view',
            'tickets.view_all',
            'tickets.view_assigned_branch',
            'tickets.view_own',
            'tickets.create',
            'tickets.assign',
            'tickets.change_priority',
            'tickets.change_status',
            'tickets.resolve',
            'tickets.close',
            'tickets.reopen',
            'comments.add_public',
            'comments.view_internal',
            'comments.add_internal',
            'attachments.upload',
            'attachments.download',
            'feature_requests.view',
            'feature_requests.create',
            'feature_requests.manage_stage',
            'reports.view_all',
            'reports.view_assigned_branch',
            'reports.export',
        ]);

        // 3. Branch EDP Role
        $branchEdpRole = Role::firstOrCreate(['name' => UserRole::BranchEdp->value, 'guard_name' => 'web']);
        $branchEdpRole->syncPermissions([
            'branches.view',
            'departments.view',
            'users.view',
            'tickets.view_assigned_branch',
            'tickets.view_own',
            'tickets.create',
            'tickets.assign',
            'tickets.change_status',
            'tickets.resolve',
            'comments.add_public',
            'comments.view_internal',
            'comments.add_internal',
            'attachments.upload',
            'attachments.download',
            'feature_requests.view',
            'feature_requests.create',
            'reports.view_assigned_branch',
        ]);

        // 4. Branch Admin Role
        $branchAdminRole = Role::firstOrCreate(['name' => UserRole::BranchAdmin->value, 'guard_name' => 'web']);
        $branchAdminRole->syncPermissions([
            'branches.view',
            'departments.view',
            'users.view',
            'users.create',
            'users.update',
            'users.manage_branch_only',
            'tickets.view_own',
            'tickets.create',
            'comments.add_public',
            'attachments.upload',
            'attachments.download',
            'feature_requests.view',
            'feature_requests.create',
        ]);

        // 5. Regular User Role
        $regularUserRole = Role::firstOrCreate(['name' => UserRole::RegularUser->value, 'guard_name' => 'web']);
        $regularUserRole->syncPermissions([
            'tickets.view_own',
            'tickets.create',
            'tickets.close',
            'tickets.reopen',
            'comments.add_public',
            'attachments.upload',
            'attachments.download',
            'feature_requests.view',
            'feature_requests.create',
        ]);
    }
}
