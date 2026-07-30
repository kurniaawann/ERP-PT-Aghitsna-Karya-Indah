<?php
namespace Database\Seeders;
use App\Models\Administrasi\RAB;
use App\Models\Administrasi\RABCategory;
use App\Models\Administrasi\RABSubCategory;
use App\Models\Administrasi\RABItem;
use App\Models\Administrasi\RABMiscellaneousCost;
use App\Models\Finance\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class RABSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $accounts = PaymentAccount::pluck('id')->toArray();

        for ($i = 1; $i <= 5; $i++) {
            $romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $month = (int) date('m');
            $rabNumber = str_pad($i, 3, '0', STR_PAD_LEFT) . '/RAB/' . $romanMonths[$month] . '/' . date('Y');

            $rab = RAB::updateOrCreate(
                ['rab_number' => $rabNumber],
                [
                    'sequence_number' => $i,
                    'date' => fake()->dateTimeBetween('-6 months', 'now'),
                    'recipient' => fake()->company(),
                    'recipient_address' => fake()->address(),
                    'intro_text' => 'Rencana anggaran biaya untuk proyek',
                    'total_amount' => 0,
                    'signed_by' => fake()->name(),
                    'division' => fake()->randomElement(['Produksi', 'Pemasaran', 'Administrasi']),
                    'selected_payment_accounts' => $accounts ? array_slice($accounts, 0, 2) : null,
                    'created_by' => $admin?->id,
                ]
            );

            $grandTotal = 0;
            for ($r = 1; $r <= 3; $r++) {
                $cat = RABCategory::create([
                    'rab_number' => $rabNumber,
                    'roman_order' => $r,
                    'category_name' => fake()->words(2, true),
                    'category_subtotal' => 0,
                    'order' => $r,
                ]);

                $catTotal = 0;
                for ($s = 1; $s <= 2; $s++) {
                    $volume = rand(10, 100);
                    $unitPrice = rand(50000, 500000);
                    $subHarga = $volume * $unitPrice;

                    $sub = RABSubCategory::create([
                        'rab_category_id' => $cat->id,
                        'number_order' => $s,
                        'subcategory_name' => fake()->words(2, true),
                        'volume' => $volume,
                        'unit' => fake()->randomElement(['pcs', 'm', 'kg']),
                        'unit_price' => $unitPrice,
                        'sub_harga' => $subHarga,
                        'order' => $s,
                    ]);
                    $catTotal += $subHarga;

                    RABItem::create([
                        'rab_subcategory_id' => $sub->id,
                        'letter_order' => 1,
                        'item_description' => fake()->words(3, true),
                        'volume' => $volume,
                        'unit' => $sub->unit,
                        'unit_price' => $unitPrice,
                        'sub_harga' => $subHarga,
                        'order' => 1,
                    ]);
                }
                $cat->update(['category_subtotal' => $catTotal]);
                $grandTotal += $catTotal;
            }

            $rab->update(['total_amount' => $grandTotal]);

            RABMiscellaneousCost::create([
                'rab_number' => $rabNumber,
                'item_order' => 1,
                'item_name' => 'Biaya tak terduga',
                'amount' => (int) ($grandTotal * 0.05),
                'order' => 1,
            ]);
        }
    }
}
