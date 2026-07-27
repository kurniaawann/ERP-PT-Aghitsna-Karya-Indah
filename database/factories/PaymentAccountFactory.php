<?php
namespace Database\Factories;

use App\Models\Finance\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentAccountFactory extends Factory
{
    protected $model = PaymentAccount::class;

    public function definition(): array
    {
        return [
            'bank_name' => fake()->randomElement(['BCA', 'Mandiri', 'BRI', 'BNI', 'CIMB Niaga', 'Danamon']),
            'account_number' => fake()->unique()->numerify('######## ########'),
            'account_holder' => fake()->name(),
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
