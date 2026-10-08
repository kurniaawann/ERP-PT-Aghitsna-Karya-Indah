<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     *
     * Revisi klien: "Tgl Jatuh Tempo" dihapus dari modul Reimbursement
     * (form, tabel, detail, PDF, Excel). Kolom due_date dibuat nullable agar
     * pengajuan baru tidak wajib mengisinya; data lama tetap dipertahankan.
     */
    public function up(): void
    {
        Schema::table('reimburses', function (Blueprint $table) {
            $table->date('due_date')->nullable()->change();
        });
    }

    /**
     * Balikkan migrasi.
     *
     * Baris tanpa due_date diisi dengan tanggal pengajuan (date) terlebih
     * dahulu agar kolom bisa dikembalikan menjadi NOT NULL.
     */
    public function down(): void
    {
        DB::table('reimburses')->whereNull('due_date')->update(['due_date' => DB::raw('`date`')]);

        Schema::table('reimburses', function (Blueprint $table) {
            $table->date('due_date')->nullable(false)->change();
        });
    }
};
