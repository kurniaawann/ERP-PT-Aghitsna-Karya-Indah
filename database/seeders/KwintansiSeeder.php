<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KwintansiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['number' => 'KW-001', 'payee' => 'PT. Sinar Abadi', 'amount' => 5000000, 'date' => now(), 'desc' => 'Pembayaran invoice proyek Gedung A', 'status' => 'approved'],
            ['number' => 'KW-002', 'payee' => 'CV. Mekar Jaya', 'amount' => 3500000, 'date' => now()->subDays(2), 'desc' => 'Pembayaran supply material', 'status' => 'approved'],
            ['number' => 'KW-003', 'payee' => 'PT. Citra Teknik', 'amount' => 7250000, 'date' => now()->subDays(4), 'desc' => 'Pembayaran jasa konsultan', 'status' => 'paid'],
            ['number' => 'KW-004', 'payee' => 'CV. Karya Bersama', 'amount' => 2100000, 'date' => now()->subDays(6), 'desc' => 'Pembayaran subkontrak pekerjaan', 'status' => 'paid'],
            ['number' => 'KW-005', 'payee' => 'PT. Anugerah Logistik', 'amount' => 4750000, 'date' => now()->subDays(8), 'desc' => 'Biaya pengiriman dan handling', 'status' => 'paid'],
        ];

        foreach ($data as $item) {
            DB::table('kwintansi')->insert([
                'number' => $item['number'],
                'payee_name' => $item['payee'],
                'amount' => $item['amount'],
                'kwintansi_date' => $item['date'],
                'description' => $item['desc'],
                'status' => $item['status'],
                'created_at' => $item['date'],
                'updated_at' => now(),
            ]);
        }
    }
}
