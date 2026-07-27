<?php
namespace Database\Factories;
use App\Models\Report\ExpenseRecap;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseRecapFactory extends Factory
{
    protected $model = ExpenseRecap::class;
    protected static int $counterA = 333;
    protected static int $counterB = 590;

    public function definition(): array
    {
        $isIncome = fake()->boolean(30);
        $year = date('Y');

        return [
            'id' => self::$counterA++ . '/' . self::$counterB++ . '/DIV.PRODUKSI/' . $year,
            'transaction_category_id' => null,
            'invoice_number' => fake()->optional()->bothify('INV-####'),
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'description' => fake()->sentence(),
            'income_amount' => $isIncome ? fake()->numberBetween(1000000, 50000000) : null,
            'expense_amount' => !$isIncome ? fake()->numberBetween(100000, 10000000) : null,
            'money_source' => fake()->randomElement(['Cash', 'Bank BCA', 'Bank Mandiri', 'BRI']),
            'sales_recap_id' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'income_amount' => fake()->numberBetween(1000000, 50000000),
            'expense_amount' => null,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'income_amount' => null,
            'expense_amount' => fake()->numberBetween(100000, 10000000),
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
