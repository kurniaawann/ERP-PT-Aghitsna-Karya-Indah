<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Kolom payroll_id pada project_financial_report_items tidak lagi dipakai:
     * entri "Upah Kerja" kini dibuat AGREGAT (satu baris per proyek + periode),
     * bukan satu baris per payroll/karyawan. Penyesuaian saat payroll dihapus
     * ditangani secara terprogram (reconcile), bukan lewat relasi FK.
     */
    public function up(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->dropForeign('pfr_items_payroll_fk');
            $table->dropIndex('pfr_items_payroll_idx');
            $table->dropColumn('payroll_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->unsignedInteger('payroll_id')->nullable()->after('payment_proof_id');
            $table->foreign('payroll_id', 'pfr_items_payroll_fk')->references('id')->on('payrolls')->cascadeOnDelete();
            $table->index('payroll_id', 'pfr_items_payroll_idx');
        });
    }
};
