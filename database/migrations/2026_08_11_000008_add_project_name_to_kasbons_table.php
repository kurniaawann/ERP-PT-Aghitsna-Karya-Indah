<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menambahkan kolom project_names (JSON) pada kasbon tim (divisi).
     *
     * Kasbon divisi bisa menargetkan satu ATAU banyak proyek sekaligus
     * (multi-project, sama seperti pemilihan proyek pada Generate Payroll).
     * Kasbon divisi ber-proyek tidak bisa dicicil manual — akan dilunasi
     * otomatis penuh saat payroll proyek + periode terkait dibayar, lalu
     * dicatat sebagai baris pengeluaran pada Laporan Keuangan Proyek.
     */
    public function up(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            $table->json('project_names')->nullable()->after('division');
        });
    }

    public function down(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            $table->dropColumn('project_names');
        });
    }
};
