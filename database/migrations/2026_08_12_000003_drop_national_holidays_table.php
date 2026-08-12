<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus tabel national_holidays.
     *
     * Fitur "Hari Libur" dihapus: hari libur kini dipilih langsung saat
     * generate slip gaji per periode, bukan dari tabel terpusat. Migrasi
     * ini menangani database lama yang sudah pernah membuat tabelnya.
     */
    public function up(): void
    {
        if (Schema::hasTable('national_holidays')) {
            Schema::drop('national_holidays');
        }
    }

    public function down(): void
    {
        // Tabel tidak lagi dibutuhkan; down() sengaja kosong.
    }
};
