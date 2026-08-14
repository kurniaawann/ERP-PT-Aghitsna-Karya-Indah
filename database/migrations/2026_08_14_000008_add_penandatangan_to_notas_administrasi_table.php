<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan snapshot petinggi penanda tangan pada notas_administrasi.
 *
 * Kolom penandatangan (JSON, nullable) menyimpan snapshot data petinggi
 * yang dipilih saat nota proyek dibuat/diubah:
 *   {
 *     id: int|null,
 *     name: string|null,
 *     position: string|null,
 *     signature_image: string|null,
 *     divisi: string|null
 *   }
 *
 * Snapshott ini dipakai blok "Hormat Kami" pada PDF nota proyek
 * (tanda tangan dari Executive::signature_image, nama & posisi petinggi,
 * dan divisi dari data Division). Disimpan sebagai snapshot agar dokumen
 * tidak berubah bila data petinggi/divisi diedit atau dihapus kemudian.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->json('penandatangan')->nullable()->after('penerima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->dropColumn('penandatangan');
        });
    }
};