<?php
namespace Database\Seeders;
use App\Models\Administrasi\ProjectQuotation;
use App\Models\Administrasi\ProjectQuotationItem;
use App\Models\Finance\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectQuotationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $accounts = PaymentAccount::pluck('id')->toArray();

        $quotations = [
            ['number' => '275/310/PT.AKI/26', 'seq' => 275, 'recipient' => 'PT. MAJU JAYA', 'amount' => 67339250],
            ['number' => '276/311/PT.AKI/26', 'seq' => 276, 'recipient' => 'CV. BERKAH JAYA', 'amount' => 25000000],
            ['number' => '277/312/PT.AKI/26', 'seq' => 277, 'recipient' => 'TOKO BANGUNAN SEJAHTERA', 'amount' => 35000000],
            ['number' => '278/313/PT.AKI/26', 'seq' => 278, 'recipient' => 'PT. KARYA UTAMA', 'amount' => 18500000],
            ['number' => '279/314/PT.AKI/26', 'seq' => 279, 'recipient' => 'UD. SUMBER REJEKI', 'amount' => 28750000],
        ];

        foreach ($quotations as $q) {
            $quotation = ProjectQuotation::updateOrCreate(
                ['quotation_number' => $q['number']],
                [
                    'sequence_number' => $q['seq'],
                    'date' => fake()->dateTimeBetween('-6 months', 'now'),
                    'subject' => 'Penawaran Harga',
                    'recipient' => $q['recipient'],
                    'project_description' => 'Proyek untuk ' . $q['recipient'],
                    'total_amount' => $q['amount'],
                    'selected_payment_accounts' => $accounts ? array_slice($accounts, 0, 2) : null,
                    'signed_by' => fake()->name(),
                    'division' => 'Produksi',
                    'created_by' => $admin?->id,
                ]
            );

            $numItems = rand(2, 5);
            for ($j = 1; $j <= $numItems; $j++) {
                $unitPrice = rand(100000, 5000000);
                $qty = rand(1, 20);
                ProjectQuotationItem::create([
                    'quotation_number' => $q['number'],
                    'order_number' => $j,
                    'description' => fake()->words(3, true),
                    'volume' => (string) $qty,
                    'unit' => fake()->randomElement(['pcs', 'm', 'kg']),
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $qty,
                ]);
            }
        }
    }
}
