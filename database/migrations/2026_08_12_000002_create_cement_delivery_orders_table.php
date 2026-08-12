<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cement_delivery_orders', function (Blueprint $table) {
            $table->string('no')->primary();
            $table->date('tanggal')->nullable();
            $table->string('proyek');
            $table->integer('volume')->default(0);
            $table->string('satuan')->nullable();
            $table->integer('harga')->default(0);
            $table->date('tanggal_lunas')->nullable();
            $table->integer('harga_modal')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cement_delivery_orders');
    }
};
