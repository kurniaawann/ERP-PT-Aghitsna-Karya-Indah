<?php
namespace Database\Factories;
use App\Models\Finance\InvoiceAlumunium;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumuniumInvoiceFactory extends Factory
{
    protected $model = InvoiceAlumunium::class;

    public function definition(): array
    {
        $items = [];
        $numItems = fake()->numberBetween(2, 5);
        $total = 0;
        for ($i = 0; $i < $numItems; $i++) {
            $price = fake()->numberBetween(500000, 5000000);
            $qty = fake()->numberBetween(1, 20);
            $items[] = [
                'description' => fake()->words(3, true),
                'quantity' => $qty,
                'unit' => fake()->randomElement(['pcs', 'm', 'kg', 'set']),
                'unit_price' => $price,
                'total_price' => $price * $qty,
            ];
            $total += $price * $qty;
        }

        return [
            'invoice_number' => null,
            'invoice_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'recipient' => fake()->company(),
            'regarding' => fake()->sentence(),
            'project_description' => fake()->sentence(),
            'items' => $items,
            'total_amount' => $total,
            'discount_type' => null,
            'discount_value' => null,
            'total_after_discount' => null,
            'dp_type' => null,
            'dp_value' => null,
            'dp_amount' => null,
            'selected_payment_accounts' => null,
        ];
    }
}
