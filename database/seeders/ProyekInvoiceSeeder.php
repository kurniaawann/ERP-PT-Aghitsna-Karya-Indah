<?php
namespace Database\Seeders;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProyekInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $accounts = PaymentAccount::pluck('id')->toArray();

        for ($i = 1; $i <= 15; $i++) {
            $items = [];
            $total = 0;
            $numItems = rand(2, 4);
            for ($j = 0; $j < $numItems; $j++) {
                $price = rand(500000, 5000000);
                $qty = rand(1, 20);
                $items[] = [
                    'description' => fake()->words(3, true),
                    'quantity' => $qty,
                    'unit' => fake()->randomElement(['pcs', 'm', 'kg', 'set']),
                    'unit_price' => $price,
                    'total_price' => $price * $qty,
                ];
                $total += $price * $qty;
            }

            InvoiceProyek::updateOrCreate(
                ['invoice_number' => 'P-2026-' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'invoice_date' => fake()->dateTimeBetween('-6 months', 'now'),
                    'recipient' => fake()->company(),
                    'regarding' => fake()->sentence(),
                    'project_description' => fake()->sentence(),
                    'items' => $items,
                    'total_amount' => $total,
                    'selected_payment_accounts' => $accounts ? array_slice($accounts, 0, 2) : null,
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
