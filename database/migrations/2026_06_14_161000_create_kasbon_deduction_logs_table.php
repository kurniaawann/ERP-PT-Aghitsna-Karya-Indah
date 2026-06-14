<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kasbon_deduction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('kasbon_code', 50);
            $table->string('employee_id', 50);
            $table->unsignedInteger('payroll_id');
            $table->integer('amount_deducted');
            $table->integer('amount_remaining_before');
            $table->integer('amount_remaining_after');
            $table->integer('period_month');
            $table->integer('period_year');
            $table->integer('week_number')->nullable();
            $table->timestamps();

            $table->foreign('kasbon_code')->references('kasbon_code')->on('kasbons')->onDelete('cascade');
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_deduction_logs');
    }
};
