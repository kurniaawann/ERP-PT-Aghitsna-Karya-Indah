<?php
namespace Database\Factories;
use App\Models\Finance\PurchaseInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseInvoiceFactory extends Factory
{
    protected $model = PurchaseInvoice::class;

    public function definition(): array
    {
        $sellingPrice = fake()->numberBetween(50000, 2000000);

        return [
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'material_name' => fake()->words(2, true),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'tax_number_code' => fake()->bothify('PN-####'),
            'item_name' => fake()->words(2, true),
            'selling_price' => $sellingPrice,
            'ppn_percentage' => 11.00,
            'ppn_tax' => (int) ($sellingPrice * 0.11),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
