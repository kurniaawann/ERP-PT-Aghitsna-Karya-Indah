<?php
namespace Database\Factories;
use App\Models\Notification\InvoiceProyekReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceProyekReminderFactory extends Factory
{
    protected $model = InvoiceProyekReminder::class;

    public function definition(): array
    {
        return [
            'invoice_number' => null,
            'invoice_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'recipient' => fake()->company(),
            'total_amount' => fake()->numberBetween(1000000, 50000000),
            'reminder_date' => fake()->dateTimeBetween('-1 month', '+30 days'),
            'status' => 'pending',
            'notification_sent_at' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'notified',
            'notification_sent_at' => now(),
        ]);
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
