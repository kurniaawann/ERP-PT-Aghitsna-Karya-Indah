<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom tipe nota pada notas_administrasi:
 * - tipe_nota   (string, default 'sewa_jual'): membedakan design nota
 *     'sewa_jual' => design nota existing (faktur, sj, biaya tambahan, PPN)
 *     'proyek'    => design nota baru (nama proyek, qty/satuan/harga)
 * - nama_proyek (string, nullable): nama proyek khusus tipe 'proyek'
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->string('tipe_nota')->default('sewa_jual')->after('id_nota');
            $table->string('nama_proyek')->nullable()->after('tipe_nota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->dropColumn(['tipe_nota', 'nama_proyek']);
        });
    }
};
