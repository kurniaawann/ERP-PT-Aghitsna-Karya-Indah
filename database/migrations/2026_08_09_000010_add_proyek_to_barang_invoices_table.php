<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom nama proyek pada barang_invoices.
 *
 * Dipakai pada desain cetak PDF/Excel: ditampilkan di bawah nama
 * penerima dan pada kalimat pembuka invoice.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->string('proyek')->nullable()->after('project_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->dropColumn('proyek');
        });
    }
};
