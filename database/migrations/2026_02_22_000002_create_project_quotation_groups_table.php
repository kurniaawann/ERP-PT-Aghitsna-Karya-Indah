<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Table: project_quotation_groups
     * Each row is a numbered section in the quotation, e.g.:
     *   No.1 → "P.1 Kayu Kamper Samarinda Oven"   subtotal: 57.000.000
     *   No.2 → "J.1 Kusen Aluminium Ykk 4 inch + Kaca 8 mm"  subtotal: 5.389.000
     */
    public function up(): void
    {
        Schema::create('project_quotation_groups', function (Blueprint $table) {
            $table->id();

            // Foreign key to the parent quotation
            $table->string('quotation_number');
            $table->foreign('quotation_number')
                ->references('quotation_number')
                ->on('project_quotations')
                ->onDelete('cascade');

            $table->unsignedSmallInteger('order_number'); // Display order: 1, 2, 3 ...
            $table->string('name');                        // e.g. "P.1 Kayu Kamper Samarinda Oven"
            $table->bigInteger('subtotal')->default(0);    // Sum of items in this group

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_quotation_groups');
    }
};
