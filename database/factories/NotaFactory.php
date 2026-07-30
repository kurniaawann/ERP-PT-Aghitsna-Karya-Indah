<?php
namespace Database\Factories;
use App\Models\Administrasi\Nota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotaFactory extends Factory
{
    protected $model = Nota::class;

    public function definition(): array
    {
        $items = [];
        $numItems = fake()->numberBetween(1, 5);
        $total = 0;
        for ($i = 0; $i < $numItems; $i++) {
            $price = fake()->numberBetween(100000, 2000000);
            $qty = fake()->numberBetween(1, 10);
            $items[] = [
                'name' => fake()->words(2, true),
                'quantity' => $qty,
                'unit_price' => $price,
                'total' => $price * $qty,
            ];
            $total += $price * $qty;
        }
        $ppnAmount = (int) ($total * 0.12);

        return [
            'id_nota' => null,
            'location' => 'Jakarta',
            'nota_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'kepada' => fake()->company(),
            'faktur_no' => fake()->bothify('FTR-####'),
            'sj_no' => fake()->bothify('SJ-####'),
            'items' => $items,
            'penerima' => fake()->name(),
            'sewa_jual' => fake()->optional()->numberBetween(100000, 500000),
            'ongkos_kirim' => fake()->optional()->numberBetween(50000, 300000),
            'bongkar_pasang' => fake()->optional()->numberBetween(100000, 500000),
            'lembur' => fake()->optional()->numberBetween(50000, 200000),
            'uang_jaminan' => fake()->optional()->numberBetween(200000, 1000000),
            'jumlah_total' => $total,
            'selected_payment_accounts' => null,
            'ppn_percentage' => 12.00,
            'ppn_amount' => $ppnAmount,
            'total_with_ppn' => $total + $ppnAmount,
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
