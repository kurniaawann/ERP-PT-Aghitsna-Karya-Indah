<?php
namespace Database\Factories;

use App\Models\Inventory\Items;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemsFactory extends Factory
{
    protected $model = Items::class;

    public function definition(): array
    {
        return [
            'id_item' => null,
            'name_item' => fake()->unique()->words(3, true),
            'quantity' => fake()->numberBetween(10, 500),
            'capital_price' => fake()->numberBetween(15000, 500000),
            'selling_price' => fake()->numberBetween(25000, 750000),
        ];
    }
}
