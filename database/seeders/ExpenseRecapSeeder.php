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

        $expenseReports = [
            // UANG MASUK PENJUALAN
            [
                'transaction_category_id' => $categories['UANG_MASUK']->id ?? null,
                'invoice_number' => 'INV-2024-001',
                'transaction_date' => Carbon::now()->subDays(25),
                'description' => 'Pembayaran Invoice Alumunium Pak Budi',
                'income_amount' => 15000000,
                'expense_amount' => null,
                'money_source' => 'Transfer BCA',
            ],
            [
                'transaction_category_id' => $categories['UANG_MASUK']->id ?? null,
                'invoice_number' => 'INV-2024-002',
                'transaction_date' => Carbon::now()->subDays(20),
                'description' => 'Penjualan Material Alumunium',
                'income_amount' => 8500000,
                'expense_amount' => null,
                'money_source' => 'Tunai',
            ],
            [
                'transaction_category_id' => $categories['UANG_MASUK']->id ?? null,
                'invoice_number' => 'INV-2024-003',
                'transaction_date' => Carbon::now()->subDays(15),
                'description' => 'Pelunasan Invoice Kusen Jendela',
                'income_amount' => 12000000,
                'expense_amount' => null,
                'money_source' => 'Transfer Mandiri',
            ],

            // UPAH KERJA / KASBON
            [
                'transaction_category_id' => $categories['UPAH_KERJA']->id ?? null,
                'invoice_number' => 'KB-2024-001',
                'transaction_date' => Carbon::now()->subDays(24),
                'description' => 'Kasbon Tukang - Pak Joko',
                'income_amount' => null,
                'expense_amount' => 500000,
                'money_source' => 'Kas Kecil',
            ],
            [
                'transaction_category_id' => $categories['UPAH_KERJA']->id ?? null,
                'invoice_number' => 'UH-2024-001',
                'transaction_date' => Carbon::now()->subDays(18),
                'description' => 'Upah Pemasangan Kusen - Tim A',
                'income_amount' => null,
                'expense_amount' => 3500000,
                'money_source' => 'Transfer',
            ],
            [
                'transaction_category_id' => $categories['UPAH_KERJA']->id ?? null,
                'invoice_number' => 'KB-2024-002',
                'transaction_date' => Carbon::now()->subDays(10),
                'description' => 'Kasbon Karyawan - Bu Siti',
                'income_amount' => null,
                'expense_amount' => 750000,
                'money_source' => 'Kas Kecil',
            ],

            // ATK / OPERASIONAL & ALAT
            [
                'transaction_category_id' => $categories['ATK_OPERASIONAL']->id ?? null,
                'invoice_number' => 'ATK-2024-001',
                'transaction_date' => Carbon::now()->subDays(22),
                'description' => 'Pembelian Alat Tulis Kantor',
                'income_amount' => null,
                'expense_amount' => 250000,
                'money_source' => 'Kas Kecil',
            ],
            [
                'transaction_category_id' => $categories['ATK_OPERASIONAL']->id ?? null,
                'invoice_number' => 'ALT-2024-001',
                'transaction_date' => Carbon::now()->subDays(16),
                'description' => 'Pembelian Gergaji Besi Baru',
                'income_amount' => null,
                'expense_amount' => 1200000,
                'money_source' => 'Transfer',
            ],
            [
                'transaction_category_id' => $categories['ATK_OPERASIONAL']->id ?? null,
                'invoice_number' => 'ATK-2024-002',
                'transaction_date' => Carbon::now()->subDays(8),
                'description' => 'Pembelian Printer dan Tinta',
                'income_amount' => null,
                'expense_amount' => 850000,
                'money_source' => 'Tunai',
            ],

            // PENGELUARAN MATERIAL
            [
                'transaction_category_id' => $categories['MATERIAL']->id ?? null,
                'invoice_number' => 'MAT-2024-001',
                'transaction_date' => Carbon::now()->subDays(21),
                'description' => 'Pembelian Baut dan Mur',
                'income_amount' => null,
                'expense_amount' => 450000,
                'money_source' => 'Tunai',
            ],
            [
                'transaction_category_id' => $categories['MATERIAL']->id ?? null,
                'invoice_number' => 'MAT-2024-002',
                'transaction_date' => Carbon::now()->subDays(14),
                'description' => 'Pembelian Kaca 5mm',
                'income_amount' => null,
                'expense_amount' => 2800000,
                'money_source' => 'Transfer',
            ],
            [
                'transaction_category_id' => $categories['MATERIAL']->id ?? null,
                'invoice_number' => 'MAT-2024-003',
                'transaction_date' => Carbon::now()->subDays(7),
                'description' => 'Pembelian Engsel dan Kunci',
                'income_amount' => null,
                'expense_amount' => 680000,
                'money_source' => 'Kas Kecil',
            ],

            // PENGELUARAN PEMBELIAN COIL
            [
                'transaction_category_id' => $categories['PEMBELIAN_COIL']->id ?? null,
                'invoice_number' => 'COIL-2024-001',
                'transaction_date' => Carbon::now()->subDays(19),
                'description' => 'Pembelian Coil Alumunium 0.4mm',
                'income_amount' => null,
                'expense_amount' => 8500000,
                'money_source' => 'Transfer BCA',
            ],
            [
                'transaction_category_id' => $categories['PEMBELIAN_COIL']->id ?? null,
                'invoice_number' => 'COIL-2024-002',
                'transaction_date' => Carbon::now()->subDays(12),
                'description' => 'Pembelian Coil Alumunium 0.5mm',
                'income_amount' => null,
                'expense_amount' => 9200000,
                'money_source' => 'Transfer Mandiri',
            ],

            // TRANSPORT
            [
                'transaction_category_id' => $categories['TRANSPORT']->id ?? null,
                'invoice_number' => 'TRP-2024-001',
                'transaction_date' => Carbon::now()->subDays(23),
                'description' => 'Pengiriman Material ke Lokasi Proyek',
                'income_amount' => null,
                'expense_amount' => 350000,
                'money_source' => 'Kas Kecil',
            ],
            [
                'transaction_category_id' => $categories['TRANSPORT']->id ?? null,
                'invoice_number' => 'TRP-2024-002',
                'transaction_date' => Carbon::now()->subDays(17),
                'description' => 'Bensin Mobil Operasional',
                'income_amount' => null,
                'expense_amount' => 500000,
                'money_source' => 'Kas Kecil',
            ],
            [
                'transaction_category_id' => $categories['TRANSPORT']->id ?? null,
                'invoice_number' => 'TRP-2024-003',
                'transaction_date' => Carbon::now()->subDays(9),
                'description' => 'Pengiriman Barang Jadi ke Customer',
                'income_amount' => null,
                'expense_amount' => 450000,
                'money_source' => 'Tunai',
            ],

            // TOKEN LISTRIK
            [
                'transaction_category_id' => $categories['TOKEN_LISTRIK']->id ?? null,
                'invoice_number' => 'TKN-2024-001',
                'transaction_date' => Carbon::now()->subDays(20),
                'description' => 'Pembelian Token Listrik Pabrik',
                'income_amount' => null,
                'expense_amount' => 1000000,
                'money_source' => 'Transfer',
            ],
            [
                'transaction_category_id' => $categories['TOKEN_LISTRIK']->id ?? null,
                'invoice_number' => 'TKN-2024-002',
                'transaction_date' => Carbon::now()->subDays(5),
                'description' => 'Pembelian Token Listrik Kantor',
                'income_amount' => null,
                'expense_amount' => 500000,
                'money_source' => 'Kas Kecil',
            ],

            // LAIN - LAIN
            [
                'transaction_category_id' => $categories['LAIN_LAIN']->id ?? null,
                'invoice_number' => 'LL-2024-001',
                'transaction_date' => Carbon::now()->subDays(13),
                'description' => 'Biaya Administrasi Bank',
                'income_amount' => null,
                'expense_amount' => 150000,
                'money_source' => 'Transfer',
            ],
            [
                'transaction_category_id' => $categories['LAIN_LAIN']->id ?? null,
                'invoice_number' => 'LL-2024-002',
                'transaction_date' => Carbon::now()->subDays(6),
                'description' => 'Konsumsi Rapat Bulanan',
                'income_amount' => null,
                'expense_amount' => 350000,
                'money_source' => 'Kas Kecil',
            ],
            [
                'transaction_category_id' => $categories['LAIN_LAIN']->id ?? null,
                'invoice_number' => 'LL-2024-003',
                'transaction_date' => Carbon::now()->subDays(2),
                'description' => 'Pembelian Perlengkapan Kebersihan',
                'income_amount' => null,
                'expense_amount' => 280000,
                'money_source' => 'Tunai',
            ],
        ];

        foreach ($expenseReports as $report) {
            ExpenseRecap::create($report);
        }

        $this->command->info('✅ Expense Recap seeder berhasil dijalankan!');
        $this->command->info('📊 Total data: ' . count($expenseReports) . ' transaksi');
    }
}
