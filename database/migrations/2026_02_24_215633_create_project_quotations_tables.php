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
        // Project Quotations (Header)
        Schema::create('project_quotations', function (Blueprint $table) {
            $table->string('quotation_number')->primary();
            $table->unsignedInteger('sequence_number');
            $table->date('date');
            $table->string('subject')->default('Penawaran Harga');
            $table->string('recipient');
            $table->string('recipient_address')->default('Ditempat');
            $table->bigInteger('total_amount')->default(0);
            $table->text('amount_in_words')->nullable();
            $table->json('selected_payment_accounts')->nullable();
            $table->string('signed_by')->nullable();
            $table->string('division')->nullable();
            $table->timestamps();

            $table->index('sequence_number');
            $table->index('date');
        });

        // Project Quotation Items (Flat structure - no groups)
        Schema::create('project_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number');
            $table->unsignedInteger('order_number');
            $table->text('description');
            $table->string('volume', 50)->nullable();
            $table->string('unit', 50)->nullable();
            $table->bigInteger('unit_price');
            $table->bigInteger('total_price');
            $table->timestamps();

            $table->foreign('quotation_number')
                ->references('quotation_number')
                ->on('project_quotations')
                ->onDelete('cascade');

            $table->index('quotation_number');
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_quotation_items');
        Schema::dropIfExists('project_quotations');
    }
};
