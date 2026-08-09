<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom lokasi pada proyek_invoices.
 *
 * Lokasi disimpan langsung di invoice agar tetap tampil walau invoice
 * dibuat tanpa penawaran (quotation). Saat invoice dibuat dari penawaran,
 * nilainya otomatis diambil dari kolom location penawaran.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->string('location')->nullable()->after('project_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
