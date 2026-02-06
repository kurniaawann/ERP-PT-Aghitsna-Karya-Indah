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
        Schema::create('kasbons', function (Blueprint $table) {
            // Primary key string dengan format KSB001, KSB002, dst
            $table->string('kasbon_code', 50)->primary();

            // Relasi ke employee (nullable karena bisa kasbon per tim tanpa employee spesifik)
            $table->string('employee_id', 50)->nullable();
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');

            // Jenis kasbon: personal (per orang) atau team (per tim)
            $table->enum('kasbon_type', ['personal', 'team'])->default('personal');

            // Informasi kasbon
            $table->integer('amount'); // Jumlah kasbon
            $table->date('kasbon_date'); // Tanggal kasbon diberikan

            // Periode kasbon untuk tracking (minggu ke berapa)
            $table->integer('week_number')->nullable(); // Minggu ke-1, 2, 3, 4
            $table->integer('period_month'); // Bulan (1-12)
            $table->integer('period_year'); // Tahun (2026)

            // Status kasbon
            $table->enum('status', ['pending', 'deducted'])->default('pending');
            // pending = belum dipotong dari payroll
            // deducted = sudah dipotong dari payroll

            // Relasi ke payroll saat kasbon dipotong (nullable karena belum tentu sudah dipotong)
            $table->unsignedInteger('deducted_in_payroll_id')->nullable();
            $table->foreign('deducted_in_payroll_id')->references('id')->on('payrolls')->onDelete('set null');

            $table->text('notes')->nullable(); // Catatan tambahan

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kasbons');
    }
};
