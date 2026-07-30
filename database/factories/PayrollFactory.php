<?php
namespace Database\Factories;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $baseSalary = fake()->numberBetween(4000000, 8000000);
        $totalWorkDays = fake()->numberBetween(20, 26);
        $presentDays = fake()->numberBetween(15, $totalWorkDays);

        return [
            'employee_id' => null,
            'period_month' => fake()->numberBetween(1, 12),
            'period_year' => 2026,
            'period_type' => 'weekly',
            'week_number' => fake()->numberBetween(1, 4),
            'period_start_date' => null,
            'period_end_date' => null,
            'base_salary' => $baseSalary,
            'total_work_days' => $totalWorkDays,
            'present_days' => $presentDays,
            'permission_days' => fake()->numberBetween(0, 2),
            'sick_days' => fake()->numberBetween(0, 2),
            'leave_days' => fake()->numberBetween(0, 1),
            'overtime_days' => fake()->numberBetween(0, 3),
            'deduction_amount' => 0,
            'overtime_total' => 0,
            'kasbon_deduction' => 0,
            'additional_expenses' => 0,
            'additional_expenses_notes' => null,
            'net_salary' => $baseSalary,
            'payment_date' => null,
            'status' => 'draft',
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
