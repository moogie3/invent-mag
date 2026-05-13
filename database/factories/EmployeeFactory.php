<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => fake()->name(),
            'employee_code' => fake()->unique()->numerify('EMP-####'),
            'department' => 'IT',
            'position' => 'Developer',
            'employment_type' => 'permanent',
            'join_date' => fake()->date(),
            'base_salary' => 10000000,
            'status' => 'active',
        ];
    }
}
