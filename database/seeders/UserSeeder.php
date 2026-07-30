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
            'email' => 'adminaghitsna1@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'adminaghitsna2@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->generalManager()->create([
            'name' => 'General Manager',
            'email' => 'generalmanageraghitsna@gmail.com',
            'password' => bcrypt('password123'),
        ]);
    }
}
