<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom kota pembuatan dokumen pada project_quotations.
 *
 * Kota ini dipakai pada baris "Kota, Tanggal" di bagian tanda tangan
 * cetak penawaran (PDF/Excel admin). Default "Jakarta" agar dokumen
 * lama tetap tampil dengan kota yang sama seperti sebelumnya.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->string('city')->nullable()->default('Jakarta')->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
};
