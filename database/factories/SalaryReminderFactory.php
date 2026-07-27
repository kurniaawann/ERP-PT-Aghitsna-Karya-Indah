<?php
namespace Database\Factories;
use App\Models\Notification\SalaryReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryReminderFactory extends Factory
{
    protected $model = SalaryReminder::class;

    public function definition(): array
    {
        return [
            'payroll_id' => null,
            'employee_id' => null,
            'period_month' => fake()->numberBetween(1, 12),
            'period_year' => 2026,
            'reminder_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'status' => 'draft',
            'notification_sent_at' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'paid']);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
