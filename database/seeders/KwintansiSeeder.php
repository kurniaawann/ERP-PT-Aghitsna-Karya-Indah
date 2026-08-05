<?php
namespace Database\Seeders;
use App\Models\Administrasi\Kwintansi;
use App\Models\Finance\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class KwintansiSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $accounts = PaymentAccount::pluck('id')->toArray();

        for ($i = 1; $i <= 15; $i++) {
            Kwintansi::updateOrCreate(
                ['id_kwintansi' => 'KW-' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'amount' => rand(500000, 20000000),
                    'payment_account_id' => $accounts ? $accounts[array_rand($accounts)] : null,
                    'include_bank' => true,
                    'is_tunai' => (bool) rand(0, 1) ?: true,
                    'is_cheque' => (bool) rand(0, 1),
                    'is_bilyet_giro' => (bool) rand(0, 1),
                    'received_from' => fake()->company(),
                    'payment_for' => fake()->sentence(),
                    'kwintansi_date' => fake()->dateTimeBetween('-3 months', 'now'),
                    'location' => 'Depok',
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
