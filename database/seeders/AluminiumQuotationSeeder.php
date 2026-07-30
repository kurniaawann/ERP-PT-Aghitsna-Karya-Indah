<?php
namespace Database\Seeders;
use App\Models\Administrasi\AluminiumQuotation;
use App\Models\Administrasi\AluminiumQuotationGroup;
use App\Models\Administrasi\AluminiumQuotationItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class AluminiumQuotationSeeder extends Seeder
{
    public function run(): void
    {
        $quotations = [
            ['number' => '1/1/ALU/25', 'seq' => 1, 'recipient' => 'PT. ALUMINIUM JAYA'],
            ['number' => '2/2/ALU/25', 'seq' => 2, 'recipient' => 'CV. KACA MANDIRI'],
            ['number' => '3/3/ALU/25', 'seq' => 3, 'recipient' => 'PT. KONSTRUKSI PRIMA'],
            ['number' => '4/4/ALU/25', 'seq' => 4, 'recipient' => 'UD. BANGUNAN SEJAHTERA'],
            ['number' => '5/5/ALU/25', 'seq' => 5, 'recipient' => 'PT. PROYEK INDAH'],
        ];

        foreach ($quotations as $q) {
            $totalAmount = 0;
            $quotation = AluminiumQuotation::updateOrCreate(
                ['quotation_number' => $q['number']],
                [
                    'sequence_number' => $q['seq'],
                    'date' => fake()->dateTimeBetween('-6 months', 'now'),
                    'subject' => 'Penawaran Harga Aluminium',
                    'recipient' => $q['recipient'],
                    'project_description' => 'Proyek aluminium untuk ' . $q['recipient'],
                    'total_amount' => 0,
                    'signed_by' => fake()->name(),
                    'division' => 'Produksi',
                ]
            );

            $numGroups = rand(1, 3);
            for ($g = 1; $g <= $numGroups; $g++) {
                $group = AluminiumQuotationGroup::create([
                    'quotation_number' => $q['number'],
                    'order_number' => $g,
                    'name' => fake()->words(2, true),
                    'subtotal' => 0,
                ]);

                $groupTotal = 0;
                $numItems = rand(1, 3);
                for ($it = 1; $it <= $numItems; $it++) {
                    $unitPrice = rand(200000, 3000000);
                    $qty = rand(1, 15);
                    AluminiumQuotationItem::create([
                        'group_id' => $group->id,
                        'order_number' => $it,
                        'description' => fake()->words(3, true),
                        'volume' => (string) $qty,
                        'unit' => fake()->randomElement(['pcs', 'm', 'kg']),
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * $qty,
                    ]);
                    $groupTotal += $unitPrice * $qty;
                }
                $group->update(['subtotal' => $groupTotal]);
                $totalAmount += $groupTotal;
            }
            $quotation->update(['total_amount' => $totalAmount]);
        }
    }
}
