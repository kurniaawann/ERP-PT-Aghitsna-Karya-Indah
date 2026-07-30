<?php
namespace Database\Factories;

use App\Models\Report\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionCategoryFactory extends Factory
{
    protected $model = TransactionCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('???_####'),
            'type' => fake()->randomElement(['INCOME', 'EXPENSE']),
            'sort_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'INCOME']);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'EXPENSE']);
    }
}
