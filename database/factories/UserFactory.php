<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'user',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'superadmin']);
    }

    public function generalManager(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'general_manager']);
    }
}
