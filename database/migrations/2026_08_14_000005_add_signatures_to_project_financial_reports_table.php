<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan snapshot petinggi untuk blok tanda tangan pada Laporan
 * Keuangan Proyek.
 *
 * Snapshot disimpan saat laporan di-update melalui modal edit: user memilih
 * petinggi (Mandor / Kabag Keuangan / Direktur) dan service menyimpan nama,
 * jabatan, serta gambar tanda tangan sebagai JSON. Dengan snapshot, cetakan
 * PDF/Excel tetap menampilkan penandatangan sesuai pilihan saat itu meskipun
 * data petinggi diubah/dihapus kemudian. Mengikuti pola `signatures` pada
 * tabel payrolls.
 */
return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('project_financial_reports', function (Blueprint $table) {
            $table->json('signatures')->nullable()->after('created_by');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('project_financial_reports', function (Blueprint $table) {
            $table->dropColumn('signatures');
        });
    }
};