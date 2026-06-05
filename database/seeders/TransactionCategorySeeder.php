<?php

namespace Database\Seeders;

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
            ['name' => 'UANG MASUK PENJUALAN', 'code' => 'UANG_MASUK', 'type' => 'INCOME', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'UPAH KERJA / KASBON', 'code' => 'UPAH_KERJA', 'type' => 'EXPENSE', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'ATK / OPERASIONAL & ALAT', 'code' => 'ATK_OPERASIONAL', 'type' => 'EXPENSE', 'sort_order' => 3, 'is_active' => true],
            ['name' => 'PENGELUARAN MATERIAL', 'code' => 'MATERIAL', 'type' => 'EXPENSE', 'sort_order' => 4, 'is_active' => true],
            ['name' => 'PENGELUARAN PEMBELIAN COIL', 'code' => 'PEMBELIAN_COIL', 'type' => 'EXPENSE', 'sort_order' => 5, 'is_active' => true],
            ['name' => 'TRANSPORT', 'code' => 'TRANSPORT', 'type' => 'EXPENSE', 'sort_order' => 6, 'is_active' => true],
            ['name' => 'TOKEN LISTRIK', 'code' => 'TOKEN_LISTRIK', 'type' => 'EXPENSE', 'sort_order' => 7, 'is_active' => true],
            ['name' => 'LAIN - LAIN', 'code' => 'LAIN_LAIN', 'type' => 'EXPENSE', 'sort_order' => 8, 'is_active' => true],
            ['name' => 'PEMELIHARAAN PERALATAN', 'code' => 'PEMELIHARAAN_ALAT', 'type' => 'EXPENSE', 'sort_order' => 9, 'is_active' => true],
            ['name' => 'BIAYA TELEKOMUNIKASI', 'code' => 'TELEKOMUNIKASI', 'type' => 'EXPENSE', 'sort_order' => 10, 'is_active' => true],
            ['name' => 'BIAYA ASURANSI', 'code' => 'ASURANSI', 'type' => 'EXPENSE', 'sort_order' => 11, 'is_active' => true],
            ['name' => 'BIAYA SEWA KANTOR', 'code' => 'SEWA_KANTOR', 'type' => 'EXPENSE', 'sort_order' => 12, 'is_active' => true],
            ['name' => 'BIAYA KESEHATAN KARYAWAN', 'code' => 'KESEHATAN_KARYAWAN', 'type' => 'EXPENSE', 'sort_order' => 13, 'is_active' => true],
            ['name' => 'BONUS & INSENTIF', 'code' => 'BONUS_INSENTIF', 'type' => 'EXPENSE', 'sort_order' => 14, 'is_active' => true],
            ['name' => 'PELATIHAN & PENGEMBANGAN', 'code' => 'PELATIHAN', 'type' => 'EXPENSE', 'sort_order' => 15, 'is_active' => true],
        ];

        // Tidak menghapus tabel karena direferensi oleh expense_recaps (foreign key).
        // Gunakan upsert berdasarkan unique key `code` supaya idempotent saat seed ulang.
        foreach ($categories as $category) {
            DB::table('transaction_categories')->updateOrInsert(
                ['code' => $category['code']],
                $category
            );
        }
    }
}


