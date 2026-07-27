<?php
namespace Database\Seeders;
use App\Models\Administrasi\Nota;
use App\Models\Finance\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $accounts = PaymentAccount::pluck('id')->toArray();

        for ($i = 1; $i <= 10; $i++) {
            $items = [];
            $total = 0;
            $numItems = rand(1, 3);
            for ($j = 0; $j < $numItems; $j++) {
                $price = rand(100000, 2000000);
                $qty = rand(1, 10);
                $items[] = [
                    'name' => fake()->words(2, true),
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total' => $price * $qty,
                ];
                $total += $price * $qty;
            }
            $ppn = (int) ($total * 0.12);

            Nota::updateOrCreate(
                ['id_nota' => 'NTA-' . str_pad($i, 3, '0', STR_PAD_LEFT) . '/AKI/26'],
                [
                    'location' => 'Jakarta',
                    'nota_date' => fake()->dateTimeBetween('-3 months', 'now'),
                    'kepada' => fake()->company(),
                    'faktur_no' => 'FTR-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'sj_no' => 'SJ-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'items' => $items,
                    'penerima' => fake()->name(),
                    'jumlah_total' => $total,
                    'selected_payment_accounts' => $accounts ? array_slice($accounts, 0, 2) : null,
                    'ppn_percentage' => 12.00,
                    'ppn_amount' => $ppn,
                    'total_with_ppn' => $total + $ppn,
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
