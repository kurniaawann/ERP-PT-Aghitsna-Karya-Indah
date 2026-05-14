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
        Schema::create('salary_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('payroll_id')->nullable();
            $table->string('employee_id')->nullable();
            $table->integer('period_month');
            $table->integer('period_year');
            $table->date('reminder_date');
            $table->enum('status', ['pending', 'notified', 'paid'])->default('pending');
            $table->datetime('notification_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_reminders');
    }
};
