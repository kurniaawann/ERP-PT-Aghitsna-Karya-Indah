<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cements', function (Blueprint $table) {
            $table->string('no')->primary();
            $table->date('tanggal')->nullable();
            $table->string('nama_proyek');
            $table->integer('jumlah')->default(0);
            $table->integer('harga')->default(0);
            $table->date('tanggal_lunas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cements');
    }
};
