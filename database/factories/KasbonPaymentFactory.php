<?php
namespace Database\Factories;
use App\Models\Sdm\KasbonPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KasbonPaymentFactory extends Factory
{
    protected $model = KasbonPayment::class;

    public function definition(): array
    {
        return [
            'kasbon_code' => null,
            'payroll_id' => null,
            'amount' => fake()->numberBetween(100000, 2000000),
            'payment_method' => fake()->randomElement(['manual', 'payroll_deduction']),
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
