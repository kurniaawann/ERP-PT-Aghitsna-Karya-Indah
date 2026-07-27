<?php
namespace Database\Seeders;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\Items;
use App\Models\Report\SalesRecap;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ItemStockOutSeeder extends Seeder
{
    public function run(): void
    {
        $items = Items::all();
        $recaps = SalesRecap::all();
        if ($items->isEmpty() || $recaps->isEmpty()) return;

        $date = Carbon::now()->format('Ymd');
        $counter = 1;
        foreach ($items->take(10) as $index => $item) {
            $recap = $recaps[$index % $recaps->count()];
            ItemStockOut::updateOrCreate(
                ['id_stock_out' => "SOT-{$date}-" . str_pad($counter, 4, '0', STR_PAD_LEFT)],
                [
                    'id_item' => $item->id_item,
                    'quantity' => 10,
                    'id_sales_recap' => $recap->id_sales_recap,
                    'project_name' => $recap->name_proyek,
                    'date' => '2026-02-01',
                ]
            );
            $counter++;
        }
    }
}
