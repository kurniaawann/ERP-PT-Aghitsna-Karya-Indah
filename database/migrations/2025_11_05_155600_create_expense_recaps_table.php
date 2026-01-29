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
        Schema::create('expense_recaps', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // Custom ID: ER-001, ER-002

            // Kategori transaksi (WAJIB)
            $table->unsignedInteger('transaction_category_id');
            $table->foreign('transaction_category_id')
                ->references('id')
                ->on('transaction_categories')
                ->onDelete('restrict');

            // Data transaksi (SEMUA NULLABLE kecuali yang di validasi controller)
            $table->string('invoice_number', 100)->nullable();
            $table->date('transaction_date')->nullable();
            $table->text('description')->nullable();
            $table->integer('income_amount')->nullable(); // INT untuk rupiah
            $table->integer('expense_amount')->nullable(); // INT untuk rupiah
            $table->string('money_source')->nullable();

            // Link ke sales recap (untuk auto-populate dari penjualan yang LUNAS)
            $table->string('sales_recap_id', 20)->nullable();
            $table->foreign('sales_recap_id')
                ->references('id_sales_recap')
                ->on('sales_recaps')
                ->onDelete('cascade');

            // Audit trail (NULL untuk auto-generate, ada nilai untuk manual input)
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index untuk performa
            $table->index('transaction_date');
            $table->index('transaction_category_id');
            $table->index('sales_recap_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_recaps');
    }
};
