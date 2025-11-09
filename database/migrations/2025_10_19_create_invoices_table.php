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
        Schema::create('aluminium_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // No : 40/40:ALU/25
            $table->date('invoice_date'); // Tanggal : 09 Oktober 2025
            $table->string('recipient'); // Kepada Yth :
            $table->text('project_description')->nullable(); // Proyek Karbela 3 / Pak Sis
            $table->text('payment_purpose')->nullable(); // Ditempatkan / Dengan ini kami sampaikan...
            $table->json('items'); // Array items: keterangan, volume, satuan, harga, jumlah
            $table->integer('total_amount'); // Jumlah (total)
            $table->text('payment_notes')->nullable(); // Catatan pembayaran
            $table->string('bank_name')->nullable(); // Nama Bank
            $table->string('account_number')->nullable(); // Nomor Rekening
            $table->string('account_holder')->nullable(); // Atas Nama
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
