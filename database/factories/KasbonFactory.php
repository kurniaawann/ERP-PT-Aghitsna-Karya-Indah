<?php
namespace Database\Factories;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KasbonFactory extends Factory
{
    protected $model = Kasbon::class;

    public function definition(): array
    {
        $amount = fake()->numberBetween(500000, 5000000);
        $paidAmount = fake()->numberBetween(0, $amount);

        return [
            'kasbon_code' => null,
            'employee_id' => null,
            'kasbon_type' => fake()->randomElement(['personal', 'team']),
            'division' => null,
            'amount' => $amount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $amount - $paidAmount,
            'kasbon_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'week_number' => fake()->numberBetween(1, 4),
            'period_month' => fake()->numberBetween(1, 12),
            'period_year' => 2026,
            'period_start_date' => null,
            'period_end_date' => null,
            'status' => 'pending',
            'payment_status' => $paidAmount == 0 ? 'unpaid' : ($paidAmount >= $amount ? 'paid' : 'partial'),
            'deducted_in_payroll_id' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function deducted(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'deducted']);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => ['kasbon_type' => 'personal']);
    }

    public function team(): static
    {
        return $this->state(fn (array $attributes) => ['kasbon_type' => 'team']);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
