<?php
namespace Database\Factories;
use App\Models\Administrasi\AluminiumQuotationGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class AluminiumQuotationGroupFactory extends Factory
{
    protected $model = AluminiumQuotationGroup::class;

    public function definition(): array
    {
        return [
            'quotation_number' => null,
            'order_number' => fake()->numberBetween(1, 5),
            'name' => fake()->words(2, true),
            'subtotal' => fake()->numberBetween(1000000, 50000000),
        ];
    }
}
