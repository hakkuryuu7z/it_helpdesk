<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'code' => 'HQ',
                'name' => 'Head Office Jakarta',
                'city' => 'Jakarta Selatan',
                'address' => 'Gedung Sentra Graha Lt. 8, Jl. Gatot Subroto Kav. 12',
                'is_active' => true,
            ],
            [
                'code' => 'BDG',
                'name' => 'Branch Bandung',
                'city' => 'Bandung',
                'address' => 'Jl. Soekarno Hatta No. 452',
                'is_active' => true,
            ],
            [
                'code' => 'SBY',
                'name' => 'Branch Surabaya',
                'city' => 'Surabaya',
                'address' => 'Jl. Raya Darmo No. 88',
                'is_active' => true,
            ],
            [
                'code' => 'MDN',
                'name' => 'Branch Medan',
                'city' => 'Medan',
                'address' => 'Jl. Putri Hijau No. 15',
                'is_active' => true,
            ],
            [
                'code' => 'SMG',
                'name' => 'Branch Semarang',
                'city' => 'Semarang',
                'address' => 'Jl. Pemuda No. 102',
                'is_active' => true,
            ],
        ];

        $standardDepartments = [
            'EDP / IT Support',
            'Finance & Accounting',
            'Operations & Logistics',
            'Human Resources',
            'Sales & Marketing',
            'General Affairs',
        ];

        foreach ($branches as $branchData) {
            $branch = Branch::firstOrCreate(
                ['code' => $branchData['code']],
                $branchData
            );

            foreach ($standardDepartments as $deptName) {
                Department::firstOrCreate([
                    'branch_id' => $branch->id,
                    'name' => $deptName,
                ], [
                    'is_active' => true,
                ]);
            }
        }
    }
}
