<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_stock_outs', function (Blueprint $table) {
            $table->string('id_stock_out')->primary();
            $table->string('id_item');
            $table->integer('quantity');
            $table->enum('kategori', ['Penjualan', 'Proyek', 'Transfer', 'Lainnya'])->default('Penjualan');
            $table->string('id_sales_recap')->nullable(); // Referensi ke sales recap jika dari penjualan
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_item')->references('id_item')->on('items')->onDelete('cascade');
            $table->foreign('id_sales_recap')->references('id_sales_recap')->on('sales_recaps')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_stock_outs');
    }
};
