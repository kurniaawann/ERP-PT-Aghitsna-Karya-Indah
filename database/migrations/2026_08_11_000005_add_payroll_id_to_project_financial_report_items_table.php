<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Menautkan item "Upah Pekerja" pada Laporan Keuangan Proyek dengan
     * payroll (Sdm\Payroll) yang menghasilkannya saat bulk pay. FK memakai
     * cascadeOnDelete sehingga saat payroll dihapus (termasuk status paid),
     * baris laporan keuangan terkait ikut terhapus otomatis.
     */
    public function up(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->unsignedInteger('payroll_id')->nullable()->after('payment_proof_id');
            $table->foreign('payroll_id', 'pfr_items_payroll_fk')->references('id')->on('payrolls')->cascadeOnDelete();
            $table->index('payroll_id', 'pfr_items_payroll_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->dropIndex('pfr_items_payroll_idx');
            $table->dropForeign('pfr_items_payroll_fk');
            $table->dropColumn('payroll_id');
        });
    }
};
