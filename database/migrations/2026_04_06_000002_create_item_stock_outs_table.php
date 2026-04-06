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
            $table->string('id_sales_recap'); // Wajib referensi ke sales recap
            $table->date('tanggal');
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_item')->references('id_item')->on('items')->onDelete('cascade');
            $table->foreign('id_sales_recap')->references('id_sales_recap')->on('sales_recaps')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_stock_outs');
    }
};
