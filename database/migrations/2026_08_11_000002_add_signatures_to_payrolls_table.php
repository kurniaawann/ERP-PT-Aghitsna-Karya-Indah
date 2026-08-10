<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan snapshot petinggi untuk blok tanda tangan pada payroll.
 *
 * Snapshot disimpan saat payroll di-generate: user memilih petinggi
 * (Disetujui oleh / Diperiksa oleh / Dibuat oleh) dan service menyimpan
 * nama, jabatan, serta gambar tanda tangan sebagai JSON. Dengan snapshot,
 * cetakan PDF tetap menampilkan penandatangan sesuai pilihan saat generate
 * meskipun data petinggi diubah/dihapus kemudian.
 */
return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->json('signatures')->nullable()->after('notes');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('signatures');
        });
    }
};
