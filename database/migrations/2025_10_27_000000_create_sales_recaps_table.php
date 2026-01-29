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
        Schema::create('sales_recaps', function (Blueprint $table) {
            $table->string('id_sales_recap')->primary();
            $table->date('date');
            $table->string('name_proyek');
            $table->json('items'); // Array of items dengan detail lengkap
            $table->integer('total_capital'); // Total harga modal
            $table->integer('total_selling'); // Total harga jual
            $table->integer('total_profit'); // Total profit
            $table->enum('status', ['Belum Lunas', 'Lunas'])->default('Belum Lunas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_recaps');
    }
};
