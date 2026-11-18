<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??#')),
            'name' => 'Branch ' . fake()->city(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
