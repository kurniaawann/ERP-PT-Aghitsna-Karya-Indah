<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expense_reports', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // Custom ID: ER-001, ER-002

            // Kategori transaksi (WAJIB)
            $table->foreignId('transaction_category_id')
                ->constrained('transaction_categories')
                ->onDelete('restrict');

            // Data transaksi (SEMUA NULLABLE kecuali yang di validasi controller)
            $table->string('invoice_number', 100)->nullable();
            $table->date('transaction_date')->nullable();
            $table->text('description')->nullable();
            $table->integer('income_amount')->nullable(); // INT untuk rupiah
            $table->integer('expense_amount')->nullable(); // INT untuk rupiah
            $table->string('money_source')->nullable();

            // Link ke sales report (untuk auto-populate dari penjualan yang LUNAS)
            $table->string('sales_report_id', 20)->nullable();
            $table->foreign('sales_report_id')
                ->references('id_sales_report')
                ->on('sales_reports')
                ->onDelete('cascade');

            // Audit trail (NULL untuk auto-generate, ada nilai untuk manual input)
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index untuk performa
            $table->index('transaction_date');
            $table->index('transaction_category_id');
            $table->index('sales_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_reports');
    }
};
