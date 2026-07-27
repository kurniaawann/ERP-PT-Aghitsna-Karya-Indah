<?php
namespace Database\Factories;

use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_code' => null,
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'division' => fake()->randomElement(['Produksi', 'Keuangan', 'SDM', 'Operasional', 'Marketing']),
            'base_salary' => fake()->randomElement([4000000, 5000000, 6000000, 7000000, 8000000]),
            'daily_wage' => fake()->numberBetween(100000, 250000),
            'join_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
