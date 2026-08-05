<?php
namespace Database\Factories;
use App\Models\Administrasi\Kwintansi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KwintansiFactory extends Factory
{
    protected $model = Kwintansi::class;

    public function definition(): array
    {
        return [
            'id_kwintansi' => null,
            'amount' => fake()->numberBetween(500000, 20000000),
            'payment_account_id' => null,
            'include_bank' => true,
            'is_tunai' => fake()->boolean(80),
            'is_cheque' => fake()->boolean(30),
            'is_bilyet_giro' => fake()->boolean(20),
            'received_from' => fake()->company(),
            'payment_for' => fake()->sentence(),
            'remaining' => null,
            'kwintansi_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'location' => 'Depok',
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
