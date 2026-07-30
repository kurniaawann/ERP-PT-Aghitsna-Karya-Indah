<?php
namespace Database\Factories;
use App\Models\Finance\InvoiceBarang;
use Illuminate\Database\Eloquent\Factories\Factory;

class BarangInvoiceFactory extends Factory
{
    protected $model = InvoiceBarang::class;

    public function definition(): array
    {
        $items = [];
        $numItems = fake()->numberBetween(2, 5);
        $totalCapital = 0;
        $totalSelling = 0;
        for ($i = 0; $i < $numItems; $i++) {
            $capital = fake()->numberBetween(100000, 1000000);
            $selling = $capital + fake()->numberBetween(50000, 500000);
            $qty = fake()->numberBetween(1, 20);
            $items[] = [
                'description' => fake()->words(3, true),
                'quantity' => $qty,
                'unit' => fake()->randomElement(['pcs', 'm', 'kg']),
                'capital_price' => $capital,
                'selling_price' => $selling,
            ];
            $totalCapital += $capital * $qty;
            $totalSelling += $selling * $qty;
        }

        return [
            'invoice_number' => null,
            'invoice_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'recipient' => fake()->company(),
            'regarding' => fake()->optional()->sentence(),
            'project_description' => fake()->sentence(),
            'items' => $items,
            'total_capital' => $totalCapital,
            'total_selling' => $totalSelling,
            'total_profit' => $totalSelling - $totalCapital,
            'sales_recap_id' => null,
            'signed_by' => fake()->optional()->name(),
            'division' => fake()->optional()->randomElement(['Direktur', 'Manager', ' Supervisor']),
        ];
    }
}
