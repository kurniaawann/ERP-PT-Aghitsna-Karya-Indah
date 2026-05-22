<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Finance\Reimburse;

class ReimburseSeeder extends Seeder
{
    public function run(): void
    {
        // reimburse_code punya PRIMARY, jadi buat idempotent
        // (hindari Duplicate entry saat seed ulang)
        \Illuminate\Support\Facades\DB::table('reimburses')->delete();

        $data = [
            ['code' => 'RMB001', 'date' => now(), 'project' => 'Proyek Gedung A', 'desc' => 'Biaya transportasi ke lokasi proyek', 'amount' => 500000, 'due' => now()->addDays(7), 'status' => 'approved', 'notes' => 'Tanda tangan sudah lengkap'],
            ['code' => 'RMB002', 'date' => now()->subDays(2), 'project' => 'Proyek Pabrik B', 'desc' => 'Biaya akomodasi dan makan perjalanan dinas', 'amount' => 1200000, 'due' => now()->addDays(5), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB003', 'date' => now()->subDays(5), 'project' => 'Proyek Renovasi C', 'desc' => 'Biaya pembelian perlengkapan pelindung diri', 'amount' => 350000, 'due' => now()->addDays(3), 'status' => 'draft', 'notes' => 'Menunggu persetujuan manajer'],
            ['code' => 'RMB004', 'date' => now()->subDays(8), 'project' => 'Proyek Kantor D', 'desc' => 'Biaya bensin kendaraan operasional', 'amount' => 750000, 'due' => now()->addDays(2), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB005', 'date' => now()->subDays(10), 'project' => 'Proyek Gedung E', 'desc' => 'Biaya tiket pesawat perjalanan ke cabang', 'amount' => 2500000, 'due' => now()->addDay(), 'status' => 'approved', 'notes' => 'Sudah tertagih'],
            ['code' => 'RMB006', 'date' => now()->subDays(3), 'project' => 'Proyek Mall F', 'desc' => 'Biaya hotel selama di lokasi proyek', 'amount' => 1500000, 'due' => now()->addDays(4), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB007', 'date' => now()->subDays(6), 'project' => 'Proyek Hotel G', 'desc' => 'Biaya makan dan minum tim kerja', 'amount' => 650000, 'due' => now()->addDays(1), 'status' => 'approved', 'notes' => 'Dengan invoice attached'],
            ['code' => 'RMB008', 'date' => now()->subDays(9), 'project' => 'Proyek Rumah Sakit H', 'desc' => 'Biaya konsultasi ahli struktur bangunan', 'amount' => 2000000, 'due' => now()->addDays(3), 'status' => 'draft', 'notes' => 'Menunggu verifikasi'],
            ['code' => 'RMB009', 'date' => now()->subDays(4), 'project' => 'Proyek Sekolah I', 'desc' => 'Biaya sewa alat berat untuk proyek', 'amount' => 3500000, 'due' => now()->addDays(6), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB010', 'date' => now()->subDays(7), 'project' => 'Proyek Café J', 'desc' => 'Biaya survey lapangan dan pengukuran', 'amount' => 800000, 'due' => now()->addDays(2), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB011', 'date' => now()->subDays(11), 'project' => 'Proyek Showroom K', 'desc' => 'Biaya pembelian cat dan finishing materials', 'amount' => 2200000, 'due' => now()->addDays(5), 'status' => 'approved', 'notes' => 'Sudah diklaim'],
            ['code' => 'RMB012', 'date' => now()->subDays(1), 'project' => 'Proyek Gudang L', 'desc' => 'Biaya sewa crane untuk pengangkatan material', 'amount' => 4000000, 'due' => now()->addDays(8), 'status' => 'draft', 'notes' => 'Dalam proses verifikasi'],
            ['code' => 'RMB013', 'date' => now()->subDays(13), 'project' => 'Proyek Kantor Cabang M', 'desc' => 'Biaya perjalanan dinas ke supplier', 'amount' => 1100000, 'due' => now()->addDays(4), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB014', 'date' => now()->subDays(15), 'project' => 'Proyek Apartemen N', 'desc' => 'Biaya dokumentasi dan foto proyek', 'amount' => 450000, 'due' => now()->addDays(2), 'status' => 'approved', 'notes' => 'Untuk laporan progress'],
            ['code' => 'RMB015', 'date' => now()->subDays(12), 'project' => 'Proyek Perumahan O', 'desc' => 'Biaya asuransi proyek tambahan', 'amount' => 3000000, 'due' => now()->addDays(7), 'status' => 'approved', 'notes' => 'Extended coverage'],
        ];

        foreach ($data as $item) {
            Reimburse::create([
                'reimburse_code' => $item['code'],
                'date' => $item['date'],
                'project_name' => $item['project'],
                'expense_description' => $item['desc'],
                'total_amount' => $item['amount'],
                'due_date' => $item['due'],
                'status' => $item['status'],
                'notes' => $item['notes'],
            ]);
        }
    }
}
