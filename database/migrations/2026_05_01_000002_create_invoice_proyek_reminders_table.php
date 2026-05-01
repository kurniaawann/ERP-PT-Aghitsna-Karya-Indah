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
        Schema::create('invoice_proyek_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->string('recipient')->nullable();
            $table->integer('total_amount')->nullable();
            $table->date('reminder_date');
            $table->enum('status', ['pending', 'notified', 'paid'])->default('pending');
            $table->datetime('notification_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('invoice_number')->references('invoice_number')->on('proyek_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_proyek_reminders');
    }
};
