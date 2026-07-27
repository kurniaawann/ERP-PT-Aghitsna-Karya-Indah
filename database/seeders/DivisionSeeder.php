<?php
namespace Database\Seeders;
use App\Models\Sdm\Division;
use App\Models\User;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $divisions = [
            'Produksi', 'Pemasaran', 'Keuangan', 'Gudang', 'Administrasi',
            'Teknologi Informasi', 'Quality Control', 'Logistik',
            'Riset & Pengembangan', 'Customer Service', 'Manajemen Proyek',
            'Procurement', 'Humas & Media', 'Keselamatan Kerja', 'Training & Development',
        ];

        foreach ($divisions as $name) {
            Division::updateOrCreate(
                ['name' => $name],
                ['description' => "Divisi $name", 'created_by' => $admin?->id]
            );
        }
    }
}
