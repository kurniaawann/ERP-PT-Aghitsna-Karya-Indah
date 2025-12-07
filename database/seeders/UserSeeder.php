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
        User::create(
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'superadmin',
            ],
        );

        // Admin - untuk mengajukan reimburse
        User::create(
            [
                'id' => Str::uuid(),
                'name' => 'Admin Keuangan',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
        );
    }
}
