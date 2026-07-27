<?php
namespace Database\Factories;

use App\Models\Report\SalesRecap;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesRecapFactory extends Factory
{
    protected $model = SalesRecap::class;
    protected static int $counter = 1;

    public function definition(): array
    {
        $items = [];
        $numItems = fake()->numberBetween(2, 5);
        $totalCapital = 0;
        $totalSelling = 0;
        for ($i = 0; $i < $numItems; $i++) {
            $capital = fake()->numberBetween(100000, 1000000);
            $selling = $capital + fake()->numberBetween(50000, 500000);
            $items[] = [
                'id_item' => 'ITM-' . str_pad(fake()->numberBetween(1, 15), 4, '0', STR_PAD_LEFT),
                'name_item' => fake()->words(2, true),
                'quantity' => fake()->numberBetween(1, 50),
                'capital_price' => $capital,
                'selling_price' => $selling,
            ];
            $totalCapital += $capital * $items[$i]['quantity'];
            $totalSelling += $selling * $items[$i]['quantity'];
        }

        return [
            'id_sales_recap' => null,
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'name_proyek' => fake()->words(3, true),
            'items' => $items,
            'total_capital' => $totalCapital,
            'total_selling' => $totalSelling,
            'total_profit' => $totalSelling - $totalCapital,
            'status' => fake()->randomElement(['Belum Lunas', 'Lunas']),
        ];
    }
}
