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
        ];

        $today = Carbon::now()->format('Ymd');
        $counter = 1;

        foreach ($data as $item) {
            DB::table('item_returns')->insert([
                'id_return' => 'RTN-' . $today . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'item_id' => $item['item'],
                'quantity' => $item['qty'],
                'return_reason' => $item['reason'],
                'return_date' => $item['date'],
                'notes' => $item['notes'],
                'created_at' => $item['date'],
                'updated_at' => now(),
            ]);
            $counter++;
        }
    }
}
