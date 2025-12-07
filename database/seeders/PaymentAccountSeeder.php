<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice\PaymentAccount;

class PaymentAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'bank_name' => 'Bank MANDIRI',
                'account_number' => '1260-0097-9284-6',
                'account_holder' => 'AKHMAD KHAIDIR',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'bank_name' => 'Bank BCA',
                'account_number' => '715.199.7531',
                'account_holder' => 'AKHMAD KHAIDIR',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'bank_name' => 'Bank BCA',
                'account_number' => '546.588.8773',
                'account_holder' => 'PT. AGHITSNA KARYA INDAH',
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($accounts as $account) {
            PaymentAccount::create($account);
        }
    }
}
