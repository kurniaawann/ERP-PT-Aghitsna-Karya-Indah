<?php
namespace Database\Factories;
use App\Models\Inventory\ItemStockOut;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemStockOutFactory extends Factory
{
    protected $model = ItemStockOut::class;

    public function definition(): array
    {
        return [
            'id_stock_out' => null,
            'id_item' => null,
            'quantity' => fake()->numberBetween(1, 50),
            'id_sales_recap' => null,
            'project_name' => fake()->words(3, true),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
