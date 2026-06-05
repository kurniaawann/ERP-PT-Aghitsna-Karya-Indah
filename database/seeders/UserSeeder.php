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
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@company.com', 'role' => 'user'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti.nurhaliza@company.com', 'role' => 'user'],
            ['name' => 'Ahmad Wijaya', 'email' => 'ahmad.wijaya@company.com', 'role' => 'user'],
            ['name' => 'Rina Kusuma', 'email' => 'rina.kusuma@company.com', 'role' => 'user'],
            ['name' => 'Yoga Pratama', 'email' => 'yoga.pratama@company.com', 'role' => 'user'],
            ['name' => 'Maya Sari', 'email' => 'maya.sari@company.com', 'role' => 'user'],
            ['name' => 'Dedi Hermawan', 'email' => 'dedi.hermawan@company.com', 'role' => 'user'],
            ['name' => 'Lisa Anggraeni', 'email' => 'lisa.anggraeni@company.com', 'role' => 'user'],
            ['name' => 'Fajar Setiawan', 'email' => 'fajar.setiawan@company.com', 'role' => 'user'],
            ['name' => 'Nina Permata', 'email' => 'nina.permata@company.com', 'role' => 'user'],
            ['name' => 'Risky Handoko', 'email' => 'risky.handoko@company.com', 'role' => 'user'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@company.com', 'role' => 'user'],
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
