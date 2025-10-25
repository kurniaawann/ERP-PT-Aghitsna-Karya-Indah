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
        // Drop the existing table and recreate with new structure
        Schema::dropIfExists('aluminium_invoices');

        Schema::create('aluminium_invoices', function (Blueprint $table) {
            $table->string('invoice_number')->primary(); // Invoice number as primary key
            $table->date('invoice_date'); // Tanggal : 09 Oktober 2025
            $table->string('recipient'); // Kepada Yth :
            $table->text('regarding'); // Regarding / Hal
            $table->text('project_description'); // Proyek Karbela 3 / Pak Sis
            $table->json('items'); // Array items: keterangan, volume, satuan, harga, jumlah
            $table->bigInteger('total_amount'); // Jumlah (total)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aluminium_invoices');

        // Restore old structure
        Schema::create('aluminium_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->string('recipient');
            $table->text('project_description')->nullable();
            $table->text('payment_purpose')->nullable();
            $table->json('items');
            $table->bigInteger('total_amount');
            $table->text('payment_notes')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->timestamps();
        });
    }
};
