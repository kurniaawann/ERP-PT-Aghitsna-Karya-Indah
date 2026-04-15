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
        // Super Admin - untuk approve/reject reimburse
        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'role' => 'superadmin',
            ],
        );

        // Admin - untuk mengajukan reimburse
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Admin Keuangan',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
        );

        // Gmail Account
        User::updateOrCreate(
            ['email' => 'radenkurni78@gmail.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Raden Kurniawan',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
        );
    }
}
