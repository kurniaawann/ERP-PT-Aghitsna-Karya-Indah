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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('material_name');
            $table->string('npwp');
            $table->string('tax_number_code');
            $table->string('item_name');
            $table->integer('selling_price');
            $table->integer('ppn_tax');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index('material_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
