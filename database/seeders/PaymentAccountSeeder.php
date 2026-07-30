<?php
namespace Database\Seeders;
use App\Models\Finance\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentAccountSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        PaymentAccount::updateOrCreate(
            ['account_number' => '791 2937 21'],
            ['bank_name' => 'BCA', 'account_holder' => 'AHMAD KHOIUR', 'is_active' => true, 'created_by' => $admin?->id]
        );

        PaymentAccount::updateOrCreate(
            ['account_number' => '126 0009 792 046'],
            ['bank_name' => 'Mandiri', 'account_holder' => 'AHMAD KHOIUR', 'is_active' => true, 'created_by' => $admin?->id]
        );
    }
}
