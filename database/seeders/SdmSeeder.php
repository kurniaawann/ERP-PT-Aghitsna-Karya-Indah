<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SdmSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            OvertimeSeeder::class,
        ]);
    }
}
