<?php
namespace Database\Factories;
use App\Models\Inventory\ItemStockIn;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemStockInFactory extends Factory
{
    protected $model = ItemStockIn::class;

    public function definition(): array
    {
        return [
            'id_stock_in' => null,
            'id_item' => null,
            'quantity' => fake()->numberBetween(5, 100),
            'capital_price' => fake()->numberBetween(15000, 500000),
            'notes' => fake()->sentence(),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
