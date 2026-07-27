<?php
namespace Database\Factories;
use App\Models\Inventory\ItemReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemReturnFactory extends Factory
{
    protected $model = ItemReturn::class;

    public function definition(): array
    {
        return [
            'id_return' => null,
            'id_item' => null,
            'id_stock_out' => null,
            'id_stock_in' => null,
            'return_type' => fake()->randomElement(['masuk', 'keluar']),
            'quantity' => fake()->numberBetween(1, 20),
            'reason' => fake()->sentence(),
            'notes' => fake()->sentence(),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
