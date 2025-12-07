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
        Schema::create('proyek_invoices', function (Blueprint $table) {
            $table->string('invoice_number')->primary(); // Invoice number as primary key (format: 1/1/PT.AKI/25)
            $table->date('invoice_date'); // Tanggal invoice
            $table->string('recipient'); // Kepada Yth
            $table->text('regarding')->nullable(); // Hal / Regarding
            $table->text('project_description'); // Deskripsi proyek
            $table->json('items'); // Array items: keterangan, volume, satuan, harga, jumlah
            $table->integer('total_amount'); // Total sebelum discount

            // Discount fields
            $table->string('discount_type')->nullable(); // 'percentage' or 'amount'
            $table->decimal('discount_value', 15, 2)->nullable(); // nilai discount
            $table->integer('total_after_discount')->nullable(); // total setelah discount

            // DP fields
            $table->string('dp_type')->nullable(); // 'percentage' or 'amount'
            $table->decimal('dp_value', 15, 2)->nullable(); // nilai DP
            $table->integer('dp_amount')->nullable(); // jumlah DP dalam rupiah

            // Payment installments (Pembayaran ke 1, ke 2, sisa, etc.)
            $table->json('payment_installments')->nullable(); // Array pembayaran: {label, amount, date}

            // Selected payment accounts
            $table->json('selected_payment_accounts')->nullable(); // Array rekening yang dipilih

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyek_invoices');
    }
};
