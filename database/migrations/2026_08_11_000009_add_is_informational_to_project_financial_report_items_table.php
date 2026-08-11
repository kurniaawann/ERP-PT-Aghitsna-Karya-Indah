<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menambahkan flag is_informational pada item Laporan Keuangan Proyek.
     *
     * Item informasi (mis. "Kasbon Pak {nama}") hanya ditampilkan sebagai
     * keterangan — tidak mempengaruhi total uang masuk/uang keluar karena
     * nominalnya sudah terpotong dari upah pada perhitungan payroll.
     */
    public function up(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->boolean('is_informational')->default(false)->after('keterangan_bon');
        });
    }

    public function down(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->dropColumn('is_informational');
        });
    }
};
