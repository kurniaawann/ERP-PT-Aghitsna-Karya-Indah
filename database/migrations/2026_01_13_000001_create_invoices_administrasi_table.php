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
        Schema::create('invoices_administrasi', function (Blueprint $table) {
            $table->string('id_invoice')->primary();
            $table->string('location')->default('Jakarta'); // Jakarta/Depok/etc
            $table->date('invoice_date'); // 24 September 2025
            $table->string('kepada'); // Kepada Yang Terhormat
            $table->string('faktur_no'); // Faktur No
            $table->string('sj_no'); // SJ.NO

            // Items akan disimpan sebagai JSON
            // [{banyaknya: 5, nama_barang: "Item A", harga_satuan: 100000, jumlah: 500000}, ...]
            $table->json('items');

            $table->string('penerima')->nullable(); // Penerima s/d

            // Optional fields
            $table->integer('sewa_jual')->nullable(); // Sewa / jual (Optional)
            $table->integer('ongkos_kirim')->nullable(); // Ongkos Kirim PP / 1x (Optional)
            $table->integer('bongkar_pasang')->nullable(); // Bongkar / pasang (Optional)
            $table->integer('lembur')->nullable(); // Lembur antar / ambil (Optional)
            $table->integer('uang_jaminan')->nullable(); // Uang Jaminan (Optional)

            $table->integer('jumlah_total'); // Jumlah total keseluruhan

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices_administrasi');
    }
};
