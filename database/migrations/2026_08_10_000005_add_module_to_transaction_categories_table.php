<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tambahkan kolom module ke tabel transaction_categories.
     *
     * Nilai: 'expense_recap' (modul Rekap Pengeluaran/Laporan Pengeluaran) atau
     * 'project_finance' (modul Laporan Keuangan Proyek). Kategori lama (milik
     * rekap pengeluaran) di-backfill ke 'expense_recap' agar perilaku modul
     * lama tidak berubah.
     */
    public function up(): void
    {
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->string('module', 30)->default('expense_recap')->after('type');
        });

        \Illuminate\Support\Facades\DB::table('transaction_categories')
            ->update(['module' => 'expense_recap']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropColumn('module');
        });
    }
};
