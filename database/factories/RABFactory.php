<?php
namespace Database\Factories;
use App\Models\Administrasi\RAB;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RABFactory extends Factory
{
    protected $model = RAB::class;
    protected static int $counter = 1;

    public function definition(): array
    {
        return [
            'rab_number' => null,
            'sequence_number' => self::$counter++,
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'recipient' => fake()->company(),
            'recipient_address' => fake()->address(),
            'intro_text' => fake()->sentence(),
            'total_amount' => 0,
            'incoming_payment' => 0,
            'amount_in_words' => null,
            'selected_payment_accounts' => null,
            'signed_by' => fake()->name(),
            'division' => fake()->randomElement(['Produksi', 'Pemasaran', 'Administrasi', 'Teknologi Informasi']),
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
