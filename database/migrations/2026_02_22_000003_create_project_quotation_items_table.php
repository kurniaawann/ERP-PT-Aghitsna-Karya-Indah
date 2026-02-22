<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Table: project_quotation_items
     * Each row is one line item inside a group, e.g.:
     *   description: "5 x 130 x 300"  volume: 1  unit: "unit"  unit_price: 16000000  total_price: 16000000
     *   description: "Engsel Pivot"   volume: 2  unit: "unit"  unit_price:  9500000  total_price: 19000000
     *   description: "Kunci Pelor Dekson" volume: null  unit: null  unit_price: 1750000  total_price: 1750000
     */
    public function up(): void
    {
        Schema::create('project_quotation_items', function (Blueprint $table) {
            $table->id();

            // Foreign key to the parent group
            $table->foreignId('group_id')
                ->constrained('project_quotation_groups')
                ->onDelete('cascade');

            $table->unsignedSmallInteger('order_number');   // Row order within the group
            $table->string('description');                   // Keterangan, e.g. "5 x 130 x 300"

            // Volume and unit can be empty/dash ("-") so nullable strings
            $table->string('volume')->nullable();            // e.g. "1", "2", "35,2", "-", null
            $table->string('unit')->nullable();              // e.g. "unit", "m¹", "m²", "set", "pasang", "-"

            $table->bigInteger('unit_price');                // Harga satuan
            $table->bigInteger('total_price');               // Jumlah (volume * unit_price, or override)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_quotation_items');
    }
};
