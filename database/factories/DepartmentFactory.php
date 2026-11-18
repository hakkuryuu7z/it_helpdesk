<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Finance', 'Operations', 'IT', 'HR', 'Logistics']) . ' ' . fake()->unique()->numberBetween(100, 999),
            'is_active' => true,
        ];
    }
}
