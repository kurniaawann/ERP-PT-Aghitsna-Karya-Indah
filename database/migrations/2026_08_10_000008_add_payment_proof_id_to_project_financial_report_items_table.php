<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menautkan item "Bon" Laporan Keuangan Proyek dengan bukti pembayaran
     * (PaymentProof) tipe 'recap' yang menghasilkannya secara otomatis.
     * Satu bukti pembayaran = satu item otomatis (unique). Saat bukti
     * pembayaran dihapus, item terkait ikut terhapus (cascade).
     */
    public function up(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_proof_id')->nullable()->after('project_financial_report_id');
            $table->foreign('payment_proof_id', 'pfr_items_proof_fk')->references('id')->on('payment_proofs')->cascadeOnDelete();
            $table->unique('payment_proof_id', 'pfr_items_proof_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_financial_report_items', function (Blueprint $table) {
            $table->dropUnique('pfr_items_proof_unique');
            $table->dropForeign('pfr_items_proof_fk');
            $table->dropColumn('payment_proof_id');
        });
    }
};
