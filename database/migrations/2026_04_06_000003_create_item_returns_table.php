<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_returns', function (Blueprint $table) {
            $table->string('id_return')->primary();
            $table->string('id_item');
            $table->string('id_stock_out')->nullable(); // Referensi ke barang keluar yang di-return
            $table->integer('quantity');
            $table->text('alasan')->nullable();
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_item')->references('id_item')->on('items')->onDelete('cascade');
            $table->foreign('id_stock_out')->references('id_stock_out')->on('item_stock_outs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_returns');
    }
};
