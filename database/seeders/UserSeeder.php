<?php

namespace Database\Seeders;

use App\Models\Users;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'superadmin', 'email' => 'superadmin@example.com', 'role' => 'superadmin'],
            ['name' => 'admin', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Raden Kurniawan', 'email' => 'radenkurni78@gmail.com', 'role' => 'admin'],
            ['name' => 'Kurniawan', 'email' => 'generalManager@gmail.com', 'role' => 'general_manager'],

        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'id' => Str::uuid(),
                    'name' => $user['name'],
                    'password' => Hash::make('password123'),
                    'role' => $user['role'],
                ],
            );
        }
    }
}
