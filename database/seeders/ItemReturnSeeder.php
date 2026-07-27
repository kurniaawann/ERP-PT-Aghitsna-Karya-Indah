<?php
namespace Database\Seeders;
use App\Models\Inventory\ItemReturn;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ItemReturnSeeder extends Seeder
{
    public function run(): void
    {
        $items = Items::all();
        if ($items->isEmpty()) return;

        $stockIns = ItemStockIn::all();
        $date = Carbon::now()->format('Ymd');
        $counter = 1;

        foreach ($items->take(5) as $index => $item) {
            ItemReturn::updateOrCreate(
                ['id_return' => "RTN-{$date}-" . str_pad($counter, 4, '0', STR_PAD_LEFT)],
                [
                    'id_item' => $item->id_item,
                    'return_type' => 'masuk',
                    'id_stock_in' => $stockIns->pluck('id_stock_in')->random() ?? null,
                    'quantity' => 5,
                    'reason' => 'Cacat produksi',
                    'notes' => 'Return dari seeder',
                    'date' => '2026-03-01',
                ]
            );
            $counter++;
        }
    }
}
