<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Sudah di-import dengan benar

class DivisionSeeder extends Seeder
{
    /**
     * Jalankan seed basis data.
     */
    public function run(): void
    {
        $divisions = [
            ['name' => 'Produksi', 'description' => 'Divisi yang menangani produksi dan manufacturing'],
            ['name' => 'Pemasaran', 'description' => 'Divisi yang menangani marketing dan penjualan'],
            ['name' => 'Keuangan', 'description' => 'Divisi yang menangani keuangan dan akuntansi'],
            ['name' => 'Gudang', 'description' => 'Divisi yang menangani pergudangan dan inventori'],
            ['name' => 'Administrasi', 'description' => 'Divisi yang menangani administrasi dan HRD'],
            ['name' => 'Teknologi Informasi', 'description' => 'Divisi yang menangani sistem dan teknologi'],
            ['name' => 'Quality Control', 'description' => 'Divisi yang menangani kontrol kualitas produk'],
            ['name' => 'Logistik', 'description' => 'Divisi yang menangani pengiriman dan distribusi'],
            ['name' => 'Riset & Pengembangan', 'description' => 'Divisi yang menangani penelitian dan inovasi'],
            ['name' => 'Customer Service', 'description' => 'Divisi yang menangani layanan pelanggan'],
            ['name' => 'Manajemen Proyek', 'description' => 'Divisi yang menangani pengelolaan proyek'],
            ['name' => 'Procurement', 'description' => 'Divisi yang menangani pengadaan barang dan layanan'],
            ['name' => 'Humas & Media', 'description' => 'Divisi yang menangani hubungan masyarakat'],
            ['name' => 'Keselamatan Kerja', 'description' => 'Divisi yang menangani K3 dan keselamatan kerja'],
            ['name' => 'Training & Development', 'description' => 'Divisi yang menangani pelatihan dan pengembangan SDM'],
        ];
        foreach ($divisions as $division) {
            DB::table('divisions')->updateOrInsert(
                ['name' => $division['name']],
                [
                    'description' => $division['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}