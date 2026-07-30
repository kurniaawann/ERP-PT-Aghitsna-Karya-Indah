<?php
namespace Database\Factories;
use App\Models\Administrasi\ProjectQuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectQuotationItemFactory extends Factory
{
    protected $model = ProjectQuotationItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(100000, 5000000);
        $qty = fake()->numberBetween(1, 20);

        return [
            'quotation_number' => null,
            'order_number' => fake()->numberBetween(1, 10),
            'description' => fake()->words(3, true),
            'volume' => (string) fake()->numberBetween(1, 100),
            'unit' => fake()->randomElement(['pcs', 'm', 'kg', 'set', 'lot']),
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $qty,
        ];
    }
}
