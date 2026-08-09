<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom PPN (opsional) pada project_quotations.
 *
 * Agar field penawaran sinkron dengan invoice proyek, PPN disimpan
 * sebagai persentase (contoh: 11.00). Saat invoice dibuat dari penawaran,
 * nilai PPN ikut terbawa (snapshot).
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->decimal('ppn', 5, 2)->nullable()->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->dropColumn('ppn');
        });
    }
};
