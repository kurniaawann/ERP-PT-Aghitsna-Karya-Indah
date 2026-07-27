<?php
namespace Database\Seeders;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\Items;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ItemStockInSeeder extends Seeder
{
    public function run(): void
    {
        $items = Items::all();
        if ($items->isEmpty()) return;

        $date = Carbon::now()->format('Ymd');
        $counter = 1;
        foreach ($items as $item) {
            ItemStockIn::updateOrCreate(
                ['id_stock_in' => "SIN-{$date}-" . str_pad($counter, 4, '0', STR_PAD_LEFT)],
                [
                    'id_item' => $item->id_item,
                    'quantity' => 50,
                    'capital_price' => $item->capital_price,
                    'notes' => 'Stock awal dari seeder',
                    'date' => '2026-01-15',
                ]
            );
            $counter++;
        }
    }
}
