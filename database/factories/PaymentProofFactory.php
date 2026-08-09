<?php
namespace Database\Factories;
use App\Models\Finance\PaymentProof;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentProofFactory extends Factory
{
    protected $model = PaymentProof::class;

    public function definition(): array
    {
        return [
            'module_type' => fake()->randomElement(['finance', 'administrasi']),
            'invoice_type' => fake()->randomElement(['proyek', 'alumunium', 'barang', 'rekap_penjualan']),
            'invoice_number' => null,
            'sales_recap_id' => null,
            'payment_stage' => fake()->optional()->numberBetween(1, 5),
            'amount' => fake()->numberBetween(1000000, 50000000),
            'file_name' => fake()->uuid() . '.jpg',
            'file_path' => 'proofs/' . fake()->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(50000, 5000000),
        ];
    }
}
