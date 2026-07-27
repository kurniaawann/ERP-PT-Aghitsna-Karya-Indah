<?php
namespace Database\Factories;
use App\Models\Administrasi\AluminiumQuotation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AluminiumQuotationFactory extends Factory
{
    protected $model = AluminiumQuotation::class;
    protected static int $counter = 1;

    public function definition(): array
    {
        return [
            'quotation_number' => null,
            'sequence_number' => self::$counter++,
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'subject' => 'Penawaran Harga',
            'recipient' => fake()->company(),
            'project_description' => fake()->sentence(),
            'total_amount' => fake()->numberBetween(5000000, 100000000),
            'amount_in_words' => null,
            'selected_payment_accounts' => null,
            'signed_by' => fake()->name(),
            'division' => fake()->randomElement(['Produksi', 'Pemasaran', 'Administrasi']),
        ];
    }
}
