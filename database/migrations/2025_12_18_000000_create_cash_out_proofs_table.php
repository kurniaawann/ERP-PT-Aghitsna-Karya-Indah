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
        Schema::create('cash_out_proofs', function (Blueprint $table) {
            $table->string('bkk_no')->primary(); // BKK-001, BKK-002, dst
            $table->string('cek_no'); // CEK-001, CEK-002, dst
            $table->date('date'); // Tanggal
            $table->string('paid_to'); // Dibayarkan Kepada
            $table->integer('amount'); // Nominal (Rp.) - menggunakan INT untuk Rupiah
            $table->text('description')->nullable(); // Keterangan
            $table->string('director')->nullable(); // Direktur (optional)
            $table->string('finance_head')->nullable(); // Kabag Keuangan (optional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_out_proofs');
    }
};
