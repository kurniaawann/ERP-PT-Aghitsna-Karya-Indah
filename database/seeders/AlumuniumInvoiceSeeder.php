<?php
namespace Database\Seeders;
use App\Models\Finance\InvoiceAlumunium;
use Illuminate\Database\Seeder;

class AlumuniumInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $recipients = [
            'PT. Karbela 3', 'PT. Gedung Perkantoran', 'PT. Gedung B', 'PT. Pabrik C',
            'PT. Renovasi D', 'PT. Kantor E', 'PT. Mall F', 'PT. Hotel G',
            'PT. RS H', 'PT. Sekolah I', 'PT. Cafe J', 'PT. Showroom K',
            'PT. Gudang L', 'PT. Cabang M', 'PT. Perumahan N',
        ];

        foreach ($recipients as $i => $recipient) {
            $items = [];
            $total = 0;
            $numItems = rand(2, 4);
            for ($j = 0; $j < $numItems; $j++) {
                $price = rand(500000, 5000000);
                $qty = rand(1, 20);
                $items[] = [
                    'description' => fake()->words(3, true),
                    'quantity' => $qty,
                    'unit' => fake()->randomElement(['pcs', 'm', 'kg']),
                    'unit_price' => $price,
                    'total_price' => $price * $qty,
                ];
                $total += $price * $qty;
            }

            InvoiceAlumunium::updateOrCreate(
                ['invoice_number' => ($i + 1) . '/' . ($i + 1) . '/ALU/25'],
                [
                    'invoice_date' => fake()->dateTimeBetween('-6 months', 'now'),
                    'recipient' => $recipient,
                    'regarding' => 'Pekerjaan ' . $recipient,
                    'project_description' => 'Proyek aluminium untuk ' . $recipient,
                    'items' => $items,
                    'total_amount' => $total,
                ]
            );
        }
    }
}
