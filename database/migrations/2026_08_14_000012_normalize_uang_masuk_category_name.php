<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalisasi nama kategori transaksi "uang masuk" yang dibuat otomatis.
 *
 * Sebelumnya kategori ini dibuat dengan nama 'UANG MASUK' (huruf kapital),
 * tidak konsisten dengan seeder ('Uang Masuk'). Migration ini mengupdate
 * record yang sudah ada agar nama konsisten menjadi 'Uang Masuk' tanpa
 * mengubah kode (code) maupun jenis (type) kategorinya.
 */
return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        DB::table('transaction_categories')
            ->where('name', 'UANG MASUK')
            ->where('type', 'INCOME')
            ->where('code', 'LIKE', 'UANG_MASUK%')
            ->update(['name' => 'Uang Masuk']);
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        DB::table('transaction_categories')
            ->where('name', 'Uang Masuk')
            ->where('type', 'INCOME')
            ->where('code', 'LIKE', 'UANG_MASUK%')
            ->update(['name' => 'UANG MASUK']);
    }
};
