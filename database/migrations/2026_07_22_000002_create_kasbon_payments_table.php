<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kasbon_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kasbon_code', 50);
            $table->foreign('kasbon_code')->references('kasbon_code')->on('kasbons')->onDelete('cascade');
            $table->unsignedInteger('payroll_id')->nullable();
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('set null');
            $table->integer('amount');
            $table->enum('payment_method', ['manual', 'payroll_deduction'])->default('manual');
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_payments');
    }
};
