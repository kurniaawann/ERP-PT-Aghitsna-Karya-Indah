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
            // Fixed roles per requirement
            ['name' => 'superadmin', 'email' => 'superadmin@example.com', 'role' => 'superadmin'],
            ['name' => 'Staf Gudang', 'email' => 'staf.gudang@example.com', 'role' => 'staf_gudang'],
            ['name' => 'Staf SDM', 'email' => 'staf.sdm@example.com', 'role' => 'staf_sdm'],
            ['name' => 'General Manager', 'email' => 'general.manager@example.com', 'role' => 'general_manager'],
            ['name' => 'Another', 'email' => 'another@example.com', 'role' => 'another'],
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
