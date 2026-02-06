<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Produksi',
                'description' => 'Divisi yang menangani produksi dan manufacturing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pemasaran',
                'description' => 'Divisi yang menangani marketing dan penjualan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Keuangan',
                'description' => 'Divisi yang menangani keuangan dan akuntansi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gudang',
                'description' => 'Divisi yang menangani pergudangan dan inventori',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Administrasi',
                'description' => 'Divisi yang menangani administrasi dan HRD',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \DB::table('divisions')->insert($divisions);
    }
}
