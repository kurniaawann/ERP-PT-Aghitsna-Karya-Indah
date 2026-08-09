<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom nama proyek pada proyek_invoices.
 *
 * Kolom `proyek` (nama proyek, mis. "Rumah Kost") dipakai pada desain
 * cetak PDF/Excel: ditampilkan di bawah nama penerima dan pada kalimat
 * pembuka "Dengan ini kami sampaikan invoice proyek {proyek} ...".
 * Field ini khusus diisi oleh role superadmin.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->string('proyek')->nullable()->after('project_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropColumn('proyek');
        });
    }
};
