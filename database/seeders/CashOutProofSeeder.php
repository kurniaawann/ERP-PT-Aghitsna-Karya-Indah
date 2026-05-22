<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashOutProofSeeder extends Seeder
{
    public function run(): void
    {
        // cash_out_proofs punya PRIMARY: bkk_no
        // supaya idempotent saat seed ulang, bersihkan dulu
        DB::table('cash_out_proofs')->delete();

        $data = [
            // bkk_no, cek_no, date, paid_to, amount, description, director, finance_head, template_type
            ['bkk_no' => 'BKK-001', 'cek_no' => 'CEK-001', 'date' => now(), 'paid_to' => 'Supplier Operasional', 'amount' => 500000, 'description' => 'Uang kas kecil untuk operasional harian', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-002', 'cek_no' => 'CEK-002', 'date' => now()->subDays(1), 'paid_to' => 'Tim Proyek', 'amount' => 750000, 'description' => 'Pembayaran makan untuk rapat kerja tim', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-003', 'cek_no' => 'CEK-003', 'date' => now()->subDays(3), 'paid_to' => 'Petty Cash', 'amount' => 1200000, 'description' => 'Biaya transport dan perjalanan dinas', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-004', 'cek_no' => 'CEK-004', 'date' => now()->subDays(5), 'paid_to' => 'Toko Perlengkapan', 'amount' => 300000, 'description' => 'Pembelian alat kantor dan perlengkapan', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-005', 'cek_no' => 'CEK-005', 'date' => now()->subDays(7), 'paid_to' => 'Karyawan', 'amount' => 2000000, 'description' => 'Pembayaran bonus karyawan bulanan', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-006', 'cek_no' => 'CEK-006', 'date' => now()->subDays(2), 'paid_to' => 'Toko ATK', 'amount' => 450000, 'description' => 'Pembelian bahan habis pakai kantor', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-007', 'cek_no' => 'CEK-007', 'date' => now()->subDays(4), 'paid_to' => 'Vendor Site', 'amount' => 850000, 'description' => 'Biaya makan pegawai site proyek', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-008', 'cek_no' => 'CEK-008', 'date' => now()->subDays(6), 'paid_to' => 'Instansi Terkait', 'amount' => 1500000, 'description' => 'Pembayaran retribusi dan perizinan', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-009', 'cek_no' => 'CEK-009', 'date' => now()->subDays(8), 'paid_to' => 'Bengkel', 'amount' => 600000, 'description' => 'Bensin dan maintenance kendaraan', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-010', 'cek_no' => 'CEK-010', 'date' => now()->subDays(9), 'paid_to' => 'PLN/Utility', 'amount' => 950000, 'description' => 'Pembayaran tagihan listrik bulanan', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-011', 'cek_no' => 'CEK-011', 'date' => now()->subDays(10), 'paid_to' => 'Safety Supplier', 'amount' => 350000, 'description' => 'Pembelian safety equipment', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-012', 'cek_no' => 'CEK-012', 'date' => now()->subDays(11), 'paid_to' => 'Konsultan', 'amount' => 2500000, 'description' => 'Pembayaran honor konsultan', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-013', 'cek_no' => 'CEK-013', 'date' => now()->subDays(12), 'paid_to' => 'Cleaning Service', 'amount' => 700000, 'description' => 'Biaya cleaning service bulanan', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-014', 'cek_no' => 'CEK-014', 'date' => now()->subDays(13), 'paid_to' => 'Asuransi', 'amount' => 1100000, 'description' => 'Pembayaran asuransi proyek', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
            ['bkk_no' => 'BKK-015', 'cek_no' => 'CEK-015', 'date' => now()->subDays(14), 'paid_to' => 'Supplier Material', 'amount' => 800000, 'description' => 'Uang muka untuk pembelian material', 'director' => null, 'finance_head' => null, 'template_type' => 'standard'],
        ];

        foreach ($data as $item) {
            DB::table('cash_out_proofs')->insert([
                'bkk_no' => $item['bkk_no'],
                'cek_no' => $item['cek_no'],
                'date' => $item['date'],
                'paid_to' => $item['paid_to'],
                'amount' => $item['amount'],
                'description' => $item['description'],
                'director' => $item['director'],
                'finance_head' => $item['finance_head'],
                'template_type' => $item['template_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
