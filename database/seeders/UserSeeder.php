<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->admin()->create([
            'name' => 'Raden Kurniawan',
            'email' => 'radenkurni78@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->generalManager()->create([
            'name' => 'Kurniawan',
            'email' => 'generalManager@gmail.com',
            'password' => bcrypt('password123'),
        ]);
    }
}
