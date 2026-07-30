<?php
namespace Database\Factories;

use App\Models\Sdm\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DivisionFactory extends Factory
{
    protected $model = Division::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
