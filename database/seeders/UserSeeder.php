<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchEdpAssignment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password');

        $hqBranch = Branch::where('code', 'HQ')->first();
        $bdgBranch = Branch::where('code', 'BDG')->first();
        $sbyBranch = Branch::where('code', 'SBY')->first();
        $smgBranch = Branch::where('code', 'SMG')->first();

        $hqItDept = Department::where('branch_id', $hqBranch->id)->where('name', 'like', '%IT%')->first();
        $bdgItDept = Department::where('branch_id', $bdgBranch->id)->where('name', 'like', '%IT%')->first();
        $bdgFinDept = Department::where('branch_id', $bdgBranch->id)->where('name', 'like', '%Finance%')->first();
        $sbyOpsDept = Department::where('branch_id', $sbyBranch->id)->where('name', 'like', '%Operations%')->first();

        // 1. Super Admin
        $superAdmin = User::updateOrCreate([
            'email' => 'superadmin@helpdesk.local',
        ], [
            'branch_id' => $hqBranch->id,
            'department_id' => $hqItDept->id ?? null,
            'name' => 'System Super Administrator',
            'username' => 'superadmin',
            'phone' => '+62811000001',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $superAdmin->syncRoles([UserRole::SuperAdmin->value]);

        // Alias for .test domain
        $superAdminTest = User::updateOrCreate([
            'email' => 'superadmin@helpdesk.test',
        ], [
            'branch_id' => $hqBranch->id,
            'department_id' => $hqItDept->id ?? null,
            'name' => 'System Super Administrator',
            'username' => 'superadmin.test',
            'phone' => '+62811000001',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $superAdminTest->syncRoles([UserRole::SuperAdmin->value]);

        // 2. Central EDP Lead
        $centralEdp = User::updateOrCreate([
            'email' => 'central.edp@helpdesk.local',
        ], [
            'branch_id' => $hqBranch->id,
            'department_id' => $hqItDept->id ?? null,
            'name' => 'Budi Santoso (Central EDP Lead)',
            'username' => 'budi.central',
            'phone' => '+62811000002',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $centralEdp->syncRoles([UserRole::CentralEdp->value]);

        // 3. Branch EDP Bandung (also services Semarang)
        $edpBandung = User::updateOrCreate([
            'email' => 'edp.bdg@helpdesk.local',
        ], [
            'branch_id' => $bdgBranch->id,
            'department_id' => $bdgItDept->id ?? null,
            'name' => 'Ahmad Hidayat (EDP Bandung & Semarang)',
            'username' => 'ahmad.edp',
            'phone' => '+62811000003',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $edpBandung->syncRoles([UserRole::BranchEdp->value]);

        BranchEdpAssignment::firstOrCreate([
            'user_id' => $edpBandung->id,
            'branch_id' => $bdgBranch->id,
        ]);
        BranchEdpAssignment::firstOrCreate([
            'user_id' => $edpBandung->id,
            'branch_id' => $smgBranch->id,
        ]);

        // 4. Branch EDP Surabaya
        $edpSurabaya = User::updateOrCreate([
            'email' => 'edp.sby@helpdesk.local',
        ], [
            'branch_id' => $sbyBranch->id,
            'department_id' => Department::where('branch_id', $sbyBranch->id)->where('name', 'like', '%IT%')->value('id'),
            'name' => 'Rendra Pratama (EDP Surabaya)',
            'username' => 'rendra.edp',
            'phone' => '+62811000004',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $edpSurabaya->syncRoles([UserRole::BranchEdp->value]);

        BranchEdpAssignment::firstOrCreate([
            'user_id' => $edpSurabaya->id,
            'branch_id' => $sbyBranch->id,
        ]);

        // 5. Branch Admin Bandung
        $adminBandung = User::updateOrCreate([
            'email' => 'admin.bdg@helpdesk.local',
        ], [
            'branch_id' => $bdgBranch->id,
            'department_id' => Department::where('branch_id', $bdgBranch->id)->where('name', 'like', '%General%')->value('id'),
            'name' => 'Siti Nurhaliza (Branch Admin BDG)',
            'username' => 'siti.admin',
            'phone' => '+62811000005',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $adminBandung->syncRoles([UserRole::BranchAdmin->value]);

        // 6. Regular User Bandung (Finance)
        $userBandung = User::updateOrCreate([
            'email' => 'user.bdg@helpdesk.local',
        ], [
            'branch_id' => $bdgBranch->id,
            'department_id' => $bdgFinDept->id ?? null,
            'name' => 'Dewi Lestari (Finance Bandung)',
            'username' => 'dewi.finance',
            'phone' => '+62811000006',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $userBandung->syncRoles([UserRole::RegularUser->value]);

        // 7. Regular User Surabaya (Operations)
        $userSurabaya = User::updateOrCreate([
            'email' => 'user.sby@helpdesk.local',
        ], [
            'branch_id' => $sbyBranch->id,
            'department_id' => $sbyOpsDept->id ?? null,
            'name' => 'Hendra Gunawan (Operations Surabaya)',
            'username' => 'hendra.ops',
            'phone' => '+62811000007',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $userSurabaya->syncRoles([UserRole::RegularUser->value]);

        // 8. Regular User HQ
        $userHq = User::updateOrCreate([
            'email' => 'user.hq@helpdesk.local',
        ], [
            'branch_id' => $hqBranch->id,
            'department_id' => $hqItDept->id ?? null,
            'name' => 'Maya Anggraini (HQ Accounting)',
            'username' => 'maya.hq',
            'phone' => '+62811000008',
            'password' => $defaultPassword,
            'is_active' => true,
        ]);
        $userHq->syncRoles([UserRole::RegularUser->value]);
    }
}
