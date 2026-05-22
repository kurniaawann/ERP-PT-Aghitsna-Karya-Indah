<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KwintansiSeeder extends Seeder
{
    public function run(): void
    {
        // kwintansi primary: id_kwintansi
        // agar aman saat rerun, bersihkan dulu
        DB::table('kwintansi')->delete();

        $data = [
            ['id_kwintansi' => 'KW-001', 'amount' => 5000000, 'received_from' => 'PT. Sinar Abadi', 'payment_for' => 'Pembayaran invoice proyek Gedung A', 'kwintansi_date' => now()],
            ['id_kwintansi' => 'KW-002', 'amount' => 3500000, 'received_from' => 'CV. Mekar Jaya', 'payment_for' => 'Pembayaran supply material', 'kwintansi_date' => now()->subDays(2)],
            ['id_kwintansi' => 'KW-003', 'amount' => 7250000, 'received_from' => 'PT. Citra Teknik', 'payment_for' => 'Pembayaran jasa konsultan', 'kwintansi_date' => now()->subDays(4)],
            ['id_kwintansi' => 'KW-004', 'amount' => 2100000, 'received_from' => 'CV. Karya Bersama', 'payment_for' => 'Pembayaran subkontrak pekerjaan', 'kwintansi_date' => now()->subDays(6)],
            ['id_kwintansi' => 'KW-005', 'amount' => 4750000, 'received_from' => 'PT. Anugerah Logistik', 'payment_for' => 'Biaya pengiriman dan handling', 'kwintansi_date' => now()->subDays(8)],
            ['id_kwintansi' => 'KW-006', 'amount' => 6200000, 'received_from' => 'PT. Semesta Konstruksi', 'payment_for' => 'Pembayaran invoice proyek Pabrik B', 'kwintansi_date' => now()->subDays(1)],
            ['id_kwintansi' => 'KW-007', 'amount' => 3800000, 'received_from' => 'CV. Andalan Teknis', 'payment_for' => 'Pembayaran jasa instalasi', 'kwintansi_date' => now()->subDays(3)],
            ['id_kwintansi' => 'KW-008', 'amount' => 8500000, 'received_from' => 'PT. Bhakti Jaya', 'payment_for' => 'Pembayaran pekerjaan renovasi', 'kwintansi_date' => now()->subDays(5)],
            ['id_kwintansi' => 'KW-009', 'amount' => 2750000, 'received_from' => 'CV. Cipta Pratama', 'payment_for' => 'Pembayaran overhead pekerjaan', 'kwintansi_date' => now()->subDays(7)],
            ['id_kwintansi' => 'KW-010', 'amount' => 5600000, 'received_from' => 'PT. Mitra Teknik', 'payment_for' => 'Biaya pemeliharaan alat', 'kwintansi_date' => now()->subDays(9)],
            ['id_kwintansi' => 'KW-011', 'amount' => 4200000, 'received_from' => 'CV. Karya Mandiri', 'payment_for' => 'Pembayaran tenaga kerja proyek', 'kwintansi_date' => now()->subDays(10)],
            ['id_kwintansi' => 'KW-012', 'amount' => 6800000, 'received_from' => 'PT. Sarana Utama', 'payment_for' => 'Pembayaran finishing material', 'kwintansi_date' => now()->subDays(11)],
            ['id_kwintansi' => 'KW-013', 'amount' => 3100000, 'received_from' => 'CV. Bina Karya', 'payment_for' => 'Pembayaran biaya administrasi', 'kwintansi_date' => now()->subDays(12)],
            ['id_kwintansi' => 'KW-014', 'amount' => 7800000, 'received_from' => 'PT. Dinamis Teknik', 'payment_for' => 'Pembayaran invoice proyek Kantor D', 'kwintansi_date' => now()->subDays(13)],
            ['id_kwintansi' => 'KW-015', 'amount' => 4900000, 'received_from' => 'CV. Solusi Konstruksi', 'payment_for' => 'Pembayaran supply bahan baku', 'kwintansi_date' => now()->subDays(14)],
        ];

        foreach ($data as $item) {
            DB::table('kwintansi')->insert([
                'id_kwintansi' => $item['id_kwintansi'],
                'amount' => $item['amount'],
                'payment_account_id' => null,
                'include_bank' => true,
                'received_from' => $item['received_from'],
                'payment_for' => $item['payment_for'],
                'remaining' => null,
                'kwintansi_date' => $item['kwintansi_date'],
                'location' => 'Depok',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
