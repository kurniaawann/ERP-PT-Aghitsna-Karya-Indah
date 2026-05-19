<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashOutProofSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['reference' => 'COP-001', 'amount' => 500000, 'purpose' => 'Uang kas kecil untuk operasional harian', 'template' => 'standard', 'date' => now()],
            ['reference' => 'COP-002', 'amount' => 750000, 'purpose' => 'Pembayaran makan untuk rapat kerja tim', 'template' => 'standard', 'date' => now()->subDays(1)],
            ['reference' => 'COP-003', 'amount' => 1200000, 'purpose' => 'Biaya transport dan perjalanan dinas', 'template' => 'transport', 'date' => now()->subDays(3)],
            ['reference' => 'COP-004', 'amount' => 300000, 'purpose' => 'Pembelian alat kantor dan perlengkapan', 'template' => 'standard', 'date' => now()->subDays(5)],
            ['reference' => 'COP-005', 'amount' => 2000000, 'purpose' => 'Pembayaran bonus karyawan bulanan', 'template' => 'payroll', 'date' => now()->subDays(7)],
        ];

        foreach ($data as $item) {
            DB::table('cash_out_proofs')->insert([
                'reference' => $item['reference'],
                'amount' => $item['amount'],
                'purpose' => $item['purpose'],
                'template_type' => $item['template'],
                'cash_out_date' => $item['date'],
                'created_at' => $item['date'],
                'updated_at' => now(),
            ]);
        }
    }
}
