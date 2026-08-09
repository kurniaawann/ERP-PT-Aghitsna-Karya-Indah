<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom DP / Uang Muka (opsional) pada project_quotations.
 *
 * Agar field penawaran sinkron dengan invoice proyek, DP disimpan dengan
 * tipe ('percentage' | 'amount'), nilai (persen atau nominal), dan jumlah
 * DP yang sudah dihitung (Rupiah). Saat invoice dibuat dari penawaran,
 * nilai DP ikut terbawa (snapshot).
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->string('dp_type')->nullable()->after('total_after_discount');
            $table->decimal('dp_value', 15, 2)->nullable()->after('dp_type');
            $table->integer('dp_amount')->nullable()->after('dp_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->dropColumn(['dp_type', 'dp_value', 'dp_amount']);
        });
    }
};
