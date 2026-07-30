<?php
namespace Database\Seeders;
use App\Models\Finance\InvoiceBarang;
use App\Models\Report\SalesRecap;
use Illuminate\Database\Seeder;

class BarangInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $recaps = SalesRecap::all();
        if ($recaps->isEmpty()) return;

        foreach ($recaps->take(10) as $i => $recap) {
            $items = [];
            $totalCapital = 0;
            $totalSelling = 0;
            foreach ($recap->items as $item) {
                $items[] = [
                    'description' => $item['name_item'] ?? 'Item',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit' => 'pcs',
                    'capital_price' => $item['capital_price'] ?? 0,
                    'selling_price' => $item['selling_price'] ?? 0,
                ];
                $totalCapital += ($item['capital_price'] ?? 0) * ($item['quantity'] ?? 1);
                $totalSelling += ($item['selling_price'] ?? 0) * ($item['quantity'] ?? 1);
            }

            InvoiceBarang::updateOrCreate(
                ['invoice_number' => 'BRG-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT) . '/PT.AKI/26'],
                [
                    'invoice_date' => $recap->date,
                    'recipient' => $recap->name_proyek,
                    'project_description' => 'Invoice barang untuk ' . $recap->name_proyek,
                    'items' => $items,
                    'total_capital' => $totalCapital,
                    'total_selling' => $totalSelling,
                    'total_profit' => $totalSelling - $totalCapital,
                    'sales_recap_id' => $recap->id_sales_recap,
                ]
            );
        }
    }
}
