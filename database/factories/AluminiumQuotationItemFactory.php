<?php
namespace Database\Factories;
use App\Models\Administrasi\AluminiumQuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class AluminiumQuotationItemFactory extends Factory
{
    protected $model = AluminiumQuotationItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(100000, 5000000);
        $qty = fake()->numberBetween(1, 20);

        return [
            'group_id' => null,
            'order_number' => fake()->numberBetween(1, 10),
            'description' => fake()->words(3, true),
            'volume' => (string) fake()->numberBetween(1, 100),
            'unit' => fake()->randomElement(['pcs', 'm', 'kg', 'set']),
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $qty,
        ];
    }
}
