<?php
namespace Database\Seeders;
use App\Models\Report\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Uang Masuk', 'code' => 'UANG_MASUK', 'type' => 'INCOME', 'sort_order' => 1],
            ['name' => 'Upah Kerja', 'code' => 'UPAH_KERJA', 'type' => 'EXPENSE', 'sort_order' => 2],
            ['name' => 'ATK & Operasional', 'code' => 'ATK_OPERASIONAL', 'type' => 'EXPENSE', 'sort_order' => 3],
            ['name' => 'Material', 'code' => 'MATERIAL', 'type' => 'EXPENSE', 'sort_order' => 4],
            ['name' => 'Pembelian Coil', 'code' => 'PEMBELIAN_COIL', 'type' => 'EXPENSE', 'sort_order' => 5],
            ['name' => 'Transport', 'code' => 'TRANSPORT', 'type' => 'EXPENSE', 'sort_order' => 6],
            ['name' => 'Token Listrik', 'code' => 'TOKEN_LISTRIK', 'type' => 'EXPENSE', 'sort_order' => 7],
            ['name' => 'Lain-lain', 'code' => 'LAIN_LAIN', 'type' => 'EXPENSE', 'sort_order' => 8],
            ['name' => 'Pemeliharaan Alat', 'code' => 'PEMELIHARAAN_ALAT', 'type' => 'EXPENSE', 'sort_order' => 9],
            ['name' => 'Telekomunikasi', 'code' => 'TELEKOMUNIKASI', 'type' => 'EXPENSE', 'sort_order' => 10],
            ['name' => 'Asuransi', 'code' => 'ASURANSI', 'type' => 'EXPENSE', 'sort_order' => 11],
            ['name' => 'Sewa Kantor', 'code' => 'SEWA_KANTOR', 'type' => 'EXPENSE', 'sort_order' => 12],
            ['name' => 'Kesehatan Karyawan', 'code' => 'KESEHATAN_KARYAWAN', 'type' => 'EXPENSE', 'sort_order' => 13],
            ['name' => 'Bonus & Insentif', 'code' => 'BONUS_INSENTIF', 'type' => 'EXPENSE', 'sort_order' => 14],
            ['name' => 'Pelatihan', 'code' => 'PELATIHAN', 'type' => 'EXPENSE', 'sort_order' => 15],
        ];

        foreach ($categories as $cat) {
            TransactionCategory::updateOrCreate(
                ['code' => $cat['code']],
                ['name' => $cat['name'], 'type' => $cat['type'], 'sort_order' => $cat['sort_order'], 'is_active' => true]
            );
        }
    }
}
