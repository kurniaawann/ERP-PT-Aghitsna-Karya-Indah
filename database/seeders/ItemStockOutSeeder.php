<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ItemStockOutSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['item' => 'ITM-0001', 'qty' => 10, 'ref' => 'SO-2026-001', 'date' => now(), 'project' => 'Proyek Gedung A', 'notes' => 'Pengiriman material ke lokasi'],
            ['item' => 'ITM-0002', 'qty' => 5, 'ref' => 'SO-2026-002', 'date' => now()->subDays(1), 'project' => 'Proyek Pabrik B', 'notes' => 'Pemakaian di proyek'],
            ['item' => 'ITM-0003', 'qty' => 20, 'ref' => 'SO-2026-003', 'date' => now()->subDays(2), 'project' => 'Proyek Kantor C', 'notes' => 'Distribusi ke cabang'],
            ['item' => 'ITM-0004', 'qty' => 15, 'ref' => 'SO-2026-004', 'date' => now()->subDays(3), 'project' => 'Proyek Renovasi D', 'notes' => 'Pemakaian perlengkapan'],
            ['item' => 'ITM-0005', 'qty' => 25, 'ref' => 'SO-2026-005', 'date' => now()->subDays(5), 'project' => 'Proyek Gedung E', 'notes' => 'Material untuk pekerjaan'],
        ];

        $today = Carbon::now()->format('Ymd');
        $counter = 1;

        foreach ($data as $item) {
            DB::table('item_stock_outs')->insert([
                'id_stock_out' => 'SOT-' . $today . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'item_id' => $item['item'],
                'quantity' => $item['qty'],
                'reference_code' => $item['ref'],
                'stock_out_date' => $item['date'],
                'project_name' => $item['project'],
                'notes' => $item['notes'],
                'created_at' => $item['date'],
                'updated_at' => now(),
            ]);
            $counter++;
        }
    }
}
