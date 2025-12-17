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
        Schema::create('document_receipts', function (Blueprint $table) {
            $table->string('id_document')->primary(); // DOC-001, DOC-002, dst
            $table->string('received_from'); // Telah Terima Dari
            $table->string('regarding'); // Perihal
            $table->string('form_of'); // Berupa
            $table->date('receipt_date'); // Hari / Tanggal
            $table->time('receipt_time'); // Jam
            $table->string('location')->default('Depok'); // Lokasi (default Depok)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_receipts');
    }
};
