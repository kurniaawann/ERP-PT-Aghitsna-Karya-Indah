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
        Schema::create('surat_perintah_kerjas', function (Blueprint $table) {
            $table->string('nomor')->primary(); // Nomor SPK, contoh: 01/SPK/AKI/DIV.PRODUKSI/2026
            $table->string('proyek');           // Nama proyek
            $table->string('lokasi');           // Lokasi proyek
            $table->date('tanggal');            // Tanggal surat

            // Pemberi Tugas: nama & alamat (tanpa jabatan)
            $table->string('pemberi_tugas_nama');
            $table->text('pemberi_tugas_alamat');

            // Yang bertanda tangan di bawah ini: nama & jabatan
            $table->string('signer_nama');
            $table->string('signer_jabatan');

            // Items disimpan sebagai JSON (struktur grup No/Kode)
            // [{no, kode, details: [{keterangan, volume, satuan, harga, jumlah}, ...]}, ...]
            $table->json('items');
            $table->bigInteger('total_amount')->default(0); // Total jumlah = volume x harga

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_perintah_kerjas');
    }
};
