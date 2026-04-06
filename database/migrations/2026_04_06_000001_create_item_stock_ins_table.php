<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_stock_ins', function (Blueprint $table) {
            $table->string('id_stock_in')->primary();
            $table->string('id_item');
            $table->integer('quantity');
            $table->integer('capital_price'); // Harga modal per unit
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->timestamps();

            // Foreign key
            $table->foreign('id_item')->references('id_item')->on('items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_stock_ins');
    }
};
