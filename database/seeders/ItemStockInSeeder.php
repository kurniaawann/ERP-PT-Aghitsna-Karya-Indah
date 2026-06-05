<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ItemStockInSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['item' => 'ITM-0001', 'qty' => 50, 'price' => 150000, 'date' => now(), 'notes' => 'Pembelian dari supplier PT. Baja Sentosa'],
            ['item' => 'ITM-0002', 'qty' => 30, 'price' => 200000, 'date' => now()->subDays(1), 'notes' => 'Stock replenishment dari gudang pusat'],
            ['item' => 'ITM-0003', 'qty' => 100, 'price' => 75000, 'date' => now()->subDays(2), 'notes' => 'Return dari proyek lama'],
            ['item' => 'ITM-0004', 'qty' => 150, 'price' => 125000, 'date' => now()->subDays(3), 'notes' => 'Pembelian untuk proyek baru'],
            ['item' => 'ITM-0005', 'qty' => 200, 'price' => 95000, 'date' => now()->subDays(5), 'notes' => 'Pembelian rutin bulanan'],
            ['item' => 'ITM-0006', 'qty' => 75, 'price' => 180000, 'date' => now()->subDays(6), 'notes' => 'Stock tambahan untuk persediaan'],
            ['item' => 'ITM-0007', 'qty' => 120, 'price' => 210000, 'date' => now()->subDays(7), 'notes' => 'Pengiriman dari supplier alternatif'],
            ['item' => 'ITM-0008', 'qty' => 90, 'price' => 140000, 'date' => now()->subDays(8), 'notes' => 'Pemesanan khusus proyek gedung'],
            ['item' => 'ITM-0009', 'qty' => 160, 'price' => 95000, 'date' => now()->subDays(9), 'notes' => 'Restock cat dan finishing materials'],
            ['item' => 'ITM-0010', 'qty' => 110, 'price' => 55000, 'date' => now()->subDays(10), 'notes' => 'Pengiriman baut dan hardware'],
            ['item' => 'ITM-0011', 'qty' => 85, 'price' => 175000, 'date' => now()->subDays(11), 'notes' => 'Stock kaca dekorasi premium'],
            ['item' => 'ITM-0012', 'qty' => 130, 'price' => 65000, 'date' => now()->subDays(12), 'notes' => 'Pembelian perlengkapan kerja'],
            ['item' => 'ITM-0013', 'qty' => 95, 'price' => 48000, 'date' => now()->subDays(13), 'notes' => 'Restocking material standar'],
            ['item' => 'ITM-0014', 'qty' => 140, 'price' => 185000, 'date' => now()->subDays(14), 'notes' => 'Pengiriman supplier utama'],
            ['item' => 'ITM-0015', 'qty' => 175, 'price' => 125000, 'date' => now()->subDays(15), 'notes' => 'Stock bulanan proyek'],
        ];

        $today = Carbon::now()->format('Ymd');
        $counter = 1;

        foreach ($data as $item) {
            DB::table('item_stock_ins')->insert([
                'id_stock_in' => 'SIN-' . $today . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'id_item' => $item['item'],
                'quantity' => $item['qty'],
                'capital_price' => $item['price'],
                'tanggal' => $item['date'],
                'keterangan' => $item['notes'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counter++;
        }
    }
}
