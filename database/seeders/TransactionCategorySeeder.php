<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'UANG MASUK PENJUALAN',
                'code' => 'UANG_MASUK',
                'type' => 'INCOME',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'UPAH KERJA / KASBON',
                'code' => 'UPAH_KERJA',
                'type' => 'EXPENSE',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'ATK / OPERASIONAL & ALAT',
                'code' => 'ATK_OPERASIONAL',
                'type' => 'EXPENSE',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'PENGELUARAN MATERIAL',
                'code' => 'MATERIAL',
                'type' => 'EXPENSE',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'PENGELUARAN PEMBELIAN COIL',
                'code' => 'PEMBELIAN_COIL',
                'type' => 'EXPENSE',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'TRANSPORT',
                'code' => 'TRANSPORT',
                'type' => 'EXPENSE',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'TOKEN LISTRIK',
                'code' => 'TOKEN_LISTRIK',
                'type' => 'EXPENSE',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'LAIN - LAIN',
                'code' => 'LAIN_LAIN',
                'type' => 'EXPENSE',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        DB::table('transaction_categories')->insert($categories);
    }
}
