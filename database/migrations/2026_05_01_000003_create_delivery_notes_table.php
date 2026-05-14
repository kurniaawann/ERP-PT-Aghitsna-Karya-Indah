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
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->string('id_delivery_note')->primary();
            $table->string('document_number'); // Nomor dokumen SJ
            $table->date('delivery_date'); // Tanggal pengiriman
            $table->string('shipper_name'); // Nama pengirim
            $table->string('shipper_address'); // Alamat pengirim
            $table->string('receiver_name'); // Nama penerima
            $table->string('receiver_address'); // Alamat penerima
            $table->text('description')->nullable(); // Deskripsi pengiriman

            // Items akan disimpan sebagai JSON
            // [{no: 1, item_name: "Item A", quantity: 5, unit: "pcs", notes: "notes"}, ...]
            $table->json('items');

            // Optional fields
            $table->string('driver_name')->nullable(); // Nama sopir
            $table->string('vehicle_number')->nullable(); // Nomor kendaraan
            $table->integer('total_quantity')->default(0); // Total jumlah barang
            $table->text('notes')->nullable(); // Catatan tambahan

            // Status
            $table->enum('status', ['draft', 'approved', 'shipped', 'delivered', 'cancelled'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
