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
