<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Report\SalesRecap;


class ItemStockOutSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['item' => 'ITM-0001', 'qty' => 10, 'ref' => 'SR-00001', 'date' => now(), 'project' => 'PROYEK KAHFI', 'notes' => 'Pengiriman material ke lokasi'],
            ['item' => 'ITM-0002', 'qty' => 5, 'ref' => 'SO-2026-002', 'date' => now()->subDays(1), 'project' => 'Proyek Pabrik B', 'notes' => 'Pemakaian di proyek'],
            ['item' => 'ITM-0003', 'qty' => 20, 'ref' => 'SO-2026-003', 'date' => now()->subDays(2), 'project' => 'Proyek Kantor C', 'notes' => 'Distribusi ke cabang'],
            ['item' => 'ITM-0004', 'qty' => 15, 'ref' => 'SO-2026-004', 'date' => now()->subDays(3), 'project' => 'Proyek Renovasi D', 'notes' => 'Pemakaian perlengkapan'],
            ['item' => 'ITM-0005', 'qty' => 25, 'ref' => 'SO-2026-005', 'date' => now()->subDays(5), 'project' => 'Proyek Gedung E', 'notes' => 'Material untuk pekerjaan'],
            ['item' => 'ITM-0006', 'qty' => 12, 'ref' => 'SO-2026-006', 'date' => now()->subDays(6), 'project' => 'Proyek Mall F', 'notes' => 'Pengiriman material baru'],
            ['item' => 'ITM-0007', 'qty' => 18, 'ref' => 'SO-2026-007', 'date' => now()->subDays(7), 'project' => 'Proyek Hotel G', 'notes' => 'Distribusi finishing materials'],
            ['item' => 'ITM-0008', 'qty' => 8, 'ref' => 'SO-2026-008', 'date' => now()->subDays(8), 'project' => 'Proyek Rumah Sakit H', 'notes' => 'Pemakaian untuk konstruksi'],
            ['item' => 'ITM-0009', 'qty' => 22, 'ref' => 'SO-2026-009', 'date' => now()->subDays(9), 'project' => 'Proyek Sekolah I', 'notes' => 'Material perlengkapan sekolah'],
            ['item' => 'ITM-0010', 'qty' => 14, 'ref' => 'SO-2026-010', 'date' => now()->subDays(10), 'project' => 'Proyek Café J', 'notes' => 'Pengiriman material dekorasi'],
            ['item' => 'ITM-0011', 'qty' => 11, 'ref' => 'SO-2026-011', 'date' => now()->subDays(11), 'project' => 'Proyek Showroom K', 'notes' => 'Pemakaian untuk display'],
            ['item' => 'ITM-0012', 'qty' => 19, 'ref' => 'SO-2026-012', 'date' => now()->subDays(12), 'project' => 'Proyek Gudang L', 'notes' => 'Material pengiriman gudang'],
            ['item' => 'ITM-0013', 'qty' => 16, 'ref' => 'SO-2026-013', 'date' => now()->subDays(13), 'project' => 'Proyek Kantor Cabang M', 'notes' => 'Pemakaian di cabang baru'],
            ['item' => 'ITM-0014', 'qty' => 13, 'ref' => 'SO-2026-014', 'date' => now()->subDays(14), 'project' => 'Proyek Apartemen N', 'notes' => 'Distribusi material proyek'],
            ['item' => 'ITM-0015', 'qty' => 24, 'ref' => 'SO-2026-015', 'date' => now()->subDays(15), 'project' => 'Proyek Perumahan O', 'notes' => 'Material pemesanan proyek besar'],
        ];

        $today = Carbon::now()->format('Ymd');
        $counter = 1;



        foreach ($data as $item) {
            $salesRecapId = SalesRecap::where('id_sales_recap', $item['ref'])->value('id_sales_recap');
            if (!$salesRecapId) {
                continue;
            }

            DB::table('item_stock_outs')->insert([
                'id_stock_out' => 'SOT-' . $today . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'id_item' => $item['item'],
                'quantity' => $item['qty'],
                'id_sales_recap' => SalesRecap::where('id_sales_recap', $item['ref'])->value('id_sales_recap'),
                'date' => $item['date']->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counter++;
        }
    }
}
