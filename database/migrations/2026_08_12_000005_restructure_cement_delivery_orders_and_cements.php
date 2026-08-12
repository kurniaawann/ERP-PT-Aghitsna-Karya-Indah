<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restrukturisasi modul Semen menjadi relasi master-detail.
 *
 * - cement_delivery_orders menjadi HEADER DO Semen, hanya menyimpan:
 *   no, tanggal (tanggal DO), tanggal_datang, tanggal_bayar, harga_modal.
 * - cements menjadi DETAIL (baris Data Semen) yang terikat ke DO lewat
 *   kolom do_no. Setiap baris menyimpan tanggal, nama_proyek, jumlah (zak),
 *   satuan, harga per zak, dan tanggal_lunas.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cement_delivery_orders', function (Blueprint $table) {
            // Pindahkan kolom yang menjadi milik baris detail semen.
            $table->dropColumn(['proyek', 'volume', 'satuan', 'harga', 'tanggal_lunas']);

            // Kolom baru khas header DO.
            $table->date('tanggal_datang')->nullable()->after('tanggal');
            $table->date('tanggal_bayar')->nullable()->after('tanggal_datang');
        });

        Schema::table('cements', function (Blueprint $table) {
            $table->string('do_no')->nullable()->after('no')->index();
            $table->string('satuan')->nullable()->default('zak')->after('jumlah');

            $table->foreign('do_no')
                ->references('no')
                ->on('cement_delivery_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cement_delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['tanggal_datang', 'tanggal_bayar']);

            $table->string('proyek');
            $table->integer('volume')->default(0);
            $table->string('satuan')->nullable();
            $table->integer('harga')->default(0);
            $table->date('tanggal_lunas')->nullable();
        });

        Schema::table('cements', function (Blueprint $table) {
            $table->dropForeign(['do_no']);
            $table->dropColumn(['do_no', 'satuan']);
        });
    }
};
