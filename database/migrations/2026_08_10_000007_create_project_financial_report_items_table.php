<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Item baris "Bon" pada Laporan Keuangan Proyek. Setiap baris terkait ke
     * satu kategori transaksi (module project_finance), memiliki uang masuk /
     * uang keluar (ditentukan oleh tipe kategori, seperti rekap pengeluaran),
     * keterangan bon, dan bukti pembayaran (opsional).
     */
    public function up(): void
    {
        Schema::create('project_financial_report_items', function (Blueprint $table) {
            $table->id();

            $table->string('project_financial_report_id', 20);
            $table->foreign('project_financial_report_id', 'pfr_items_report_fk')->references('id')->on('project_financial_reports')->cascadeOnDelete();

            $table->unsignedInteger('transaction_category_id');
            $table->foreign('transaction_category_id', 'pfr_items_category_fk')->references('id')->on('transaction_categories');

            $table->date('transaction_date');
            $table->string('description', 1000);
            $table->unsignedBigInteger('income_amount')->nullable();
            $table->unsignedBigInteger('expense_amount')->nullable();
            $table->string('keterangan_bon', 255)->nullable();
            $table->string('proof_file')->nullable();
            $table->string('proof_file_name', 255)->nullable();

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index('transaction_category_id', 'pfr_items_category_idx');
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_financial_report_items');
    }
};
