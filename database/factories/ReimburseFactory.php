<?php
namespace Database\Factories;
use App\Models\Finance\Reimburse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReimburseFactory extends Factory
{
    protected $model = Reimburse::class;

    public function definition(): array
    {
        return [
            'reimburse_code' => null,
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'project_name' => fake()->words(3, true),
            'expense_description' => fake()->sentence(),
            'total_amount' => fake()->numberBetween(100000, 5000000),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'status_changed_at' => null,
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'draft']);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'status_changed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'status_changed_at' => now(),
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
