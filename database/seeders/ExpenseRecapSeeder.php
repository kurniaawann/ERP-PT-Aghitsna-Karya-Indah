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
        // Get all categories
        $categories = TransactionCategory::all()->keyBy('code');

        // Generate data for 2025 and early 2026
        $this->generateExpenseData($categories);

        $this->command->info('✅ Expense Recap seeder berhasil dijalankan dengan data lengkap 2025-2026!');
    }

    /**
     * Generate expense data for multiple months
     */
    private function generateExpenseData($categories)
    {
        $counter = 1;

        // Data untuk setiap bulan dari Januari 2025 - Januari 2026
        for ($year = 2025; $year <= 2026; $year++) {
            $maxMonth = ($year == 2026) ? 1 : 12; // Untuk 2026 hanya sampai Januari

            for ($month = 1; $month <= $maxMonth; $month++) {
                // UANG MASUK - 2-3 transaksi per bulan
                for ($i = 1; $i <= rand(2, 3); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['UANG_MASUK']->id ?? null,
                        'invoice_number' => 'INV-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getIncomeDescription($i),
                        'income_amount' => rand(5000, 50000) * 1000,
                        'expense_amount' => null,
                        'money_source' => $this->getRandomPaymentMethod(),
                    ]);
                }

                // UPAH KERJA / KASBON - 3-4 transaksi per bulan
                for ($i = 1; $i <= rand(3, 4); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['UPAH_KERJA']->id ?? null,
                        'invoice_number' => ($i % 2 == 0 ? 'KB-' : 'UH-') . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getWageDescription($i),
                        'income_amount' => null,
                        'expense_amount' => rand(500, 5000) * 1000,
                        'money_source' => ($i % 2 == 0) ? 'Kas Kecil' : 'Transfer',
                    ]);
                }

                // ATK / OPERASIONAL - 2-3 transaksi per bulan
                for ($i = 1; $i <= rand(2, 3); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['ATK_OPERASIONAL']->id ?? null,
                        'invoice_number' => 'ATK-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getAtkDescription($i),
                        'income_amount' => null,
                        'expense_amount' => rand(200, 2000) * 1000,
                        'money_source' => $this->getRandomPaymentMethod(),
                    ]);
                }

                // MATERIAL - 2-4 transaksi per bulan
                for ($i = 1; $i <= rand(2, 4); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['MATERIAL']->id ?? null,
                        'invoice_number' => 'MAT-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getMaterialDescription($i),
                        'income_amount' => null,
                        'expense_amount' => rand(400, 5000) * 1000,
                        'money_source' => $this->getRandomPaymentMethod(),
                    ]);
                }

                // PEMBELIAN COIL - 1-2 transaksi per bulan (transaksi besar)
                for ($i = 1; $i <= rand(1, 2); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['PEMBELIAN_COIL']->id ?? null,
                        'invoice_number' => 'COIL-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getCoilDescription($i),
                        'income_amount' => null,
                        'expense_amount' => rand(7000, 15000) * 1000,
                        'money_source' => 'Transfer ' . ($i % 2 == 0 ? 'BCA' : 'Mandiri'),
                    ]);
                }

                // TRANSPORT - 2-3 transaksi per bulan
                for ($i = 1; $i <= rand(2, 3); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['TRANSPORT']->id ?? null,
                        'invoice_number' => 'TRP-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getTransportDescription($i),
                        'income_amount' => null,
                        'expense_amount' => rand(300, 800) * 1000,
                        'money_source' => 'Kas Kecil',
                    ]);
                }

                // TOKEN LISTRIK - 1-2 transaksi per bulan
                for ($i = 1; $i <= rand(1, 2); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['TOKEN_LISTRIK']->id ?? null,
                        'invoice_number' => 'TKN-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getTokenDescription($i),
                        'income_amount' => null,
                        'expense_amount' => rand(500, 1500) * 1000,
                        'money_source' => $i % 2 == 0 ? 'Transfer' : 'Kas Kecil',
                    ]);
                }

                // LAIN-LAIN - 2-3 transaksi per bulan
                for ($i = 1; $i <= rand(2, 3); $i++) {
                    ExpenseRecap::create([
                        'id' => 'ER-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                        'transaction_category_id' => $categories['LAIN_LAIN']->id ?? null,
                        'invoice_number' => 'LL-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'transaction_date' => Carbon::create($year, $month, rand(1, 28)),
                        'description' => $this->getMiscDescription($i),
                        'income_amount' => null,
                        'expense_amount' => rand(150, 600) * 1000,
                        'money_source' => $this->getRandomPaymentMethod(),
                    ]);
                }
            }
        }
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
