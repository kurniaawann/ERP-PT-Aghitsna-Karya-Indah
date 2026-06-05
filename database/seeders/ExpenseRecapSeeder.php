<?php

namespace Database\Seeders;

use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ExpenseRecapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan tidak duplicate saat seed diulang (expense_recaps punya PRIMARY pada `id`)
        // Karena FK ke transaction_categories tidak relevan untuk penghapusan expense_recaps,
        // maka kita bersihkan expense_recaps terlebih dulu.
        ExpenseRecap::query()->delete();

        $categories = TransactionCategory::all()->keyBy('code');
        $expenseData = [];
        $counter = 1;

        // 15 diverse expense records
        $expenses = [
            ['cat' => 'UANG_MASUK', 'inv' => 'INV-001', 'date' => now()->subDays(10), 'desc' => 'Penjualan Produk', 'income' => 50000000, 'expense' => null, 'source' => 'Transfer'],
            ['cat' => 'UPAH_KERJA', 'inv' => 'UH-001', 'date' => now()->subDays(8), 'desc' => 'Gaji Karyawan Tetap', 'income' => null, 'expense' => 3500000, 'source' => 'Kas Kecil'],
            ['cat' => 'MATERIAL', 'inv' => 'MAT-001', 'date' => now()->subDays(7), 'desc' => 'Pembelian Bahan Baku', 'income' => null, 'expense' => 8000000, 'source' => 'Transfer BCA'],
            ['cat' => 'PEMBELIAN_COIL', 'inv' => 'COIL-001', 'date' => now()->subDays(6), 'desc' => 'Pembelian Coil Alumunium', 'income' => null, 'expense' => 12000000, 'source' => 'Transfer Mandiri'],
            ['cat' => 'ATK_OPERASIONAL', 'inv' => 'ATK-001', 'date' => now()->subDays(5), 'desc' => 'Pembelian Alat Tulis Kantor', 'income' => null, 'expense' => 750000, 'source' => 'Kas Kecil'],
            ['cat' => 'TRANSPORT', 'inv' => 'TRP-001', 'date' => now()->subDays(4), 'desc' => 'Pengiriman Material', 'income' => null, 'expense' => 2500000, 'source' => 'Kas Kecil'],
            ['cat' => 'TOKEN_LISTRIK', 'inv' => 'TKN-001', 'date' => now()->subDays(3), 'desc' => 'Pembelian Token Listrik', 'income' => null, 'expense' => 1000000, 'source' => 'Transfer'],
            ['cat' => 'LAIN_LAIN', 'inv' => 'LL-001', 'date' => now()->subDays(2), 'desc' => 'Konsumsi Rapat', 'income' => null, 'expense' => 450000, 'source' => 'Kas Kecil'],
            ['cat' => 'UANG_MASUK', 'inv' => 'INV-002', 'date' => now()->subDays(9), 'desc' => 'Bonus Proyek', 'income' => 25000000, 'expense' => null, 'source' => 'Transfer'],
            ['cat' => 'UPAH_KERJA', 'inv' => 'KB-001', 'date' => now()->subDays(11), 'desc' => 'Kasbon Karyawan', 'income' => null, 'expense' => 2000000, 'source' => 'Transfer'],
            ['cat' => 'MATERIAL', 'inv' => 'MAT-002', 'date' => now()->subDays(12), 'desc' => 'Pembelian Accesories', 'income' => null, 'expense' => 3500000, 'source' => 'Transfer BCA'],
            ['cat' => 'TRANSPORT', 'inv' => 'TRP-002', 'date' => now()->subDays(13), 'desc' => 'Biaya Bensin Operasional', 'income' => null, 'expense' => 1200000, 'source' => 'Kas Kecil'],
            ['cat' => 'ATK_OPERASIONAL', 'inv' => 'ATK-002', 'date' => now()->subDays(14), 'desc' => 'Pembelian Perlengkapan Kebersihan', 'income' => null, 'expense' => 600000, 'source' => 'Kas Kecil'],
            ['cat' => 'LAIN_LAIN', 'inv' => 'LL-002', 'date' => now()->subDays(15), 'desc' => 'Biaya Maintenance Peralatan', 'income' => null, 'expense' => 1800000, 'source' => 'Transfer'],
            ['cat' => 'PEMBELIAN_COIL', 'inv' => 'COIL-002', 'date' => now(), 'desc' => 'Coil untuk Produksi', 'income' => null, 'expense' => 15000000, 'source' => 'Transfer Mandiri'],
        ];

        foreach ($expenses as $expense) {
            $expenseData[] = [
                'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                'transaction_category_id' => $categories[$expense['cat']]->id ?? null,
                'invoice_number' => $expense['inv'],
                'transaction_date' => $expense['date'],
                'description' => $expense['desc'],
                'income_amount' => $expense['income'],
                'expense_amount' => $expense['expense'],
                'money_source' => $expense['source'],
            ];
        }

        foreach ($expenseData as $data) {
            ExpenseRecap::create($data);
        }

        $this->command->info('✅ Expense Recap seeder berhasil dijalankan dengan 15 records!');
    }



    // Helper methods untuk generate description
    private function getIncomeDescription($index)
    {
        $descriptions = [
            'Pembayaran Invoice Alumunium Project',
            'Pelunasan Invoice Kusen',
            'Pembayaran DP Project Baru',
            'Penjualan Material Alumunium',
            'Pelunasan Termin Project',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getWageDescription($index)
    {
        $descriptions = [
            'Kasbon Tukang Tim A',
            'Upah Pemasangan Kusen',
            'Kasbon Karyawan Produksi',
            'Upah Lembur Karyawan',
            'Bonus Kinerja Karyawan',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getAtkDescription($index)
    {
        $descriptions = [
            'Pembelian Alat Tulis Kantor',
            'Pembelian Peralatan Workshop',
            'Pembelian Printer dan Tinta',
            'Pembelian Perlengkapan Kantor',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getMaterialDescription($index)
    {
        $descriptions = [
            'Pembelian Baut dan Mur',
            'Pembelian Kaca dan Accessories',
            'Pembelian Engsel dan Handle',
            'Pembelian Cat dan Finishing',
            'Pembelian Sealant dan Lem',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getCoilDescription($index)
    {
        $descriptions = [
            'Pembelian Coil Alumunium 0.4mm',
            'Pembelian Coil Alumunium 0.5mm',
            'Pembelian Coil Alumunium 0.6mm',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getTransportDescription($index)
    {
        $descriptions = [
            'Pengiriman Material ke Lokasi Project',
            'Bensin Mobil Operasional',
            'Pengiriman Barang Jadi ke Customer',
            'Biaya Tol dan Parkir',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getTokenDescription($index)
    {
        $descriptions = [
            'Pembelian Token Listrik Pabrik',
            'Pembelian Token Listrik Kantor',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getMiscDescription($index)
    {
        $descriptions = [
            'Biaya Administrasi Bank',
            'Konsumsi Rapat Bulanan',
            'Pembelian Perlengkapan Kebersihan',
            'Biaya Maintenance Peralatan',
            'Iuran Keamanan',
        ];
        return $descriptions[$index % count($descriptions)];
    }

    private function getRandomPaymentMethod()
    {
        $methods = ['Tunai', 'Transfer BCA', 'Transfer Mandiri', 'Kas Kecil'];
        return $methods[array_rand($methods)];
    }
}
