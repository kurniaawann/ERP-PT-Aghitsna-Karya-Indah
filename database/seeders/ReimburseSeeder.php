<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Finance\Reimburse;

class ReimburseSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'RMB001', 'date' => now(), 'project' => 'Proyek Gedung A', 'desc' => 'Biaya transportasi ke lokasi proyek', 'amount' => 500000, 'due' => now()->addDays(7), 'status' => 'approved', 'notes' => 'Tanda tangan sudah lengkap'],
            ['code' => 'RMB002', 'date' => now()->subDays(2), 'project' => 'Proyek Pabrik B', 'desc' => 'Biaya akomodasi dan makan perjalanan dinas', 'amount' => 1200000, 'due' => now()->addDays(5), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB003', 'date' => now()->subDays(5), 'project' => 'Proyek Renovasi C', 'desc' => 'Biaya pembelian perlengkapan pelindung diri', 'amount' => 350000, 'due' => now()->addDays(3), 'status' => 'draft', 'notes' => 'Menunggu persetujuan manajer'],
            ['code' => 'RMB004', 'date' => now()->subDays(8), 'project' => 'Proyek Kantor D', 'desc' => 'Biaya bensin kendaraan operasional', 'amount' => 750000, 'due' => now()->addDays(2), 'status' => 'approved', 'notes' => null],
            ['code' => 'RMB005', 'date' => now()->subDays(10), 'project' => 'Proyek Gedung E', 'desc' => 'Biaya tiket pesawat perjalanan ke cabang', 'amount' => 2500000, 'due' => now()->addDay(), 'status' => 'approved', 'notes' => 'Sudah tertagih'],
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
