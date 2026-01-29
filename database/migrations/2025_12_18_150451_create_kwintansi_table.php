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
        Schema::create('kwintansi', function (Blueprint $table) {
            $table->string('id_kwintansi')->primary();
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->boolean('include_bank')->default(true); // Checkbox untuk show/hide bank
            $table->string('received_from');
            $table->text('payment_for');
            $table->decimal('remaining', 15, 2)->nullable();
            $table->date('kwintansi_date');
            $table->string('location')->default('Depok');
            $table->timestamps();

            $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kwintansi');
    }
};
