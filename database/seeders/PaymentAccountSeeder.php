<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Finance\PaymentAccount;

class PaymentAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'bank_name' => 'BCA',
                'account_number' => '791 2937 21',
                'account_holder' => 'AHMAD KHOIUR',
                'is_active' => true,
            ],
            [
                'bank_name' => 'Mandiri',
                'account_number' => '126 0009 792 046',
                'account_holder' => 'AHMAD KHOIUR',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            PaymentAccount::firstOrCreate(
                [
                    'bank_name' => $account['bank_name'],
                    'account_number' => $account['account_number'],
                ],
                $account
            );
        }
    }
}
