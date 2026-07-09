<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ItemReturnSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['item' => 'ITM-0001', 'qty' => 2, 'reason' => 'Cacat produksi', 'date' => now(), 'notes' => 'Dimensi tidak sesuai spesifikasi'],
            ['item' => 'ITM-0002', 'qty' => 3, 'reason' => 'Kesalahan pengiriman', 'date' => now()->subDays(2), 'notes' => 'Tidak sesuai dengan pesanan'],
            ['item' => 'ITM-0003', 'qty' => 1, 'reason' => 'Rusak saat transportasi', 'date' => now()->subDays(4), 'notes' => 'Kemasan rusak'],
            ['item' => 'ITM-0004', 'qty' => 4, 'reason' => 'Kualitas tidak sesuai', 'date' => now()->subDays(6), 'notes' => 'Finishing kurang sempurna'],
            ['item' => 'ITM-0005', 'qty' => 2, 'reason' => 'Cacat pabrikan', 'date' => now()->subDays(8), 'notes' => 'Ada gelembung udara pada permukaan'],
            ['item' => 'ITM-0006', 'qty' => 3, 'reason' => 'Warna tidak sesuai', 'date' => now()->subDays(10), 'notes' => 'Shade warna berbeda dari sample'],
            ['item' => 'ITM-0007', 'qty' => 2, 'reason' => 'Kerusakan di lapangan', 'date' => now()->subDays(11), 'notes' => 'Kaca pecah saat pemasangan'],
            ['item' => 'ITM-0008', 'qty' => 1, 'reason' => 'Cacat pada tepi', 'date' => now()->subDays(12), 'notes' => 'Tepi tajam dan tidak halus'],
            ['item' => 'ITM-0009', 'qty' => 5, 'reason' => 'Tidak memenuhi standar', 'date' => now()->subDays(13), 'notes' => 'Uji kualitas tidak lulus'],
            ['item' => 'ITM-0010', 'qty' => 2, 'reason' => 'Kesalahan ukuran', 'date' => now()->subDays(14), 'notes' => 'Ukuran panjang kurang 5mm'],
            ['item' => 'ITM-0011', 'qty' => 4, 'reason' => 'Cacat produksi', 'date' => now()->subDays(15), 'notes' => 'Potongan tidak rata'],
            ['item' => 'ITM-0012', 'qty' => 1, 'reason' => 'Rusak saat transportasi', 'date' => now()->subDays(16), 'notes' => 'Penyok di bagian sisi'],
            ['item' => 'ITM-0013', 'qty' => 3, 'reason' => 'Kualitas tidak sesuai', 'date' => now()->subDays(17), 'notes' => 'Pernis tidak merata'],
            ['item' => 'ITM-0014', 'qty' => 2, 'reason' => 'Cacat pabrikan', 'date' => now()->subDays(18), 'notes' => 'Goresan pada permukaan'],
            ['item' => 'ITM-0015', 'qty' => 3, 'reason' => 'Tidak memenuhi standar', 'date' => now()->subDays(19), 'notes' => 'Hasil pengecatan kurang sempurna'],
        ];

        $today = Carbon::now()->format('Ymd');
        $counter = 1;

        // item_returns schema (migration):
        // id_return, id_item, id_stock_out (nullable), quantity, reason (nullable), notes (nullable), date
        foreach ($data as $item) {
            DB::table('item_returns')->insert([
                'id_return' => 'RTN-' . $today . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),

                // match migration/model column name
                'id_item' => $item['item'],

                // id_stock_out nullable: ambil salah satu existing stock_out jika ada, supaya relasi tidak kosong
                // tapi kalau tabel item_stock_outs belum terisi maka biarkan null (nullable).
                'id_stock_out' => DB::table('item_stock_outs')->value('id_stock_out'),

                'quantity' => $item['qty'],
                'reason' => $item['reason'],
                'notes' => $item['notes'],
                'date' => $item['date'],

                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counter++;
        }
    }
}
