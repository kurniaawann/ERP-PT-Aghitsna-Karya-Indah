<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan snapshot petinggi untuk blok tanda tangan pada Bukti Kas Keluar.
 *
 * Kolom signatures (JSON, nullable) menyimpan snapshot petinggi yang dipilih
 * untuk ketiga peran penandatangan:
 *   - direktur      : petinggi penanda Direktur/Manager
 *   - kabag_keuangan: petinggi penanda Kabag Keuangan
 *   - diterima_oleh : petinggi penanda Diterima Oleh
 *
 * Setiap snapshot berisi { id, name, position, signature_image } sehingga
 * cetakan PDF menampilkan gambar tanda tangan dan jabatan dari modul Data
 * Petinggi, serta tidak berubah bila data petinggi diubah/dihapus kemudian.
 * Mengikuti pola `signatures` pada tabel payrolls & project_financial_reports.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->json('signatures')->nullable()->after('finance_head');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->dropColumn('signatures');
        });
    }
};