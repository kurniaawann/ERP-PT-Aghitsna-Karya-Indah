<?php
namespace Database\Factories;
use App\Models\Administrasi\CashOutProof;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashOutProofFactory extends Factory
{
    protected $model = CashOutProof::class;

    public function definition(): array
    {
        return [
            'bkk_no' => null,
            'cek_no' => null,
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'paid_to' => fake()->company(),
            'amount' => fake()->numberBetween(100000, 10000000),
            'description' => fake()->sentence(),
            'director' => 'Zulkarnain,ST.,MT',
            'finance_head' => 'Kamila,AMK',
            'template_type' => 'standard',
            'created_by' => null,
        ];
    }

    public function hollow(): static
    {
        return $this->state(fn (array $attributes) => ['template_type' => 'hollow']);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
