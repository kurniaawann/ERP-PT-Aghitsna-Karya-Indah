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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Periode gaji
            $table->integer('period_month'); // Bulan (1-12)
            $table->integer('period_year'); // Tahun (2025)

            $table->integer('base_salary'); // Gaji pokok

            // Rekap Absensi
            $table->integer('total_work_days')->default(0); // Total hari kerja di bulan itu
            $table->integer('present_days')->default(0); // Jumlah hari hadir
            $table->integer('permission_days')->default(0); // Jumlah hari izin
            $table->integer('sick_days')->default(0); // Jumlah hari sakit
            $table->integer('leave_days')->default(0); // Jumlah hari cuti
            $table->integer('overtime_days')->default(0); // Jumlah hari lembur

            // Potongan & Tambahan
            $table->integer('deduction_amount')->default(0); // Total potongan (permission+sick+leave) × 30000
            $table->integer('overtime_total')->default(0); // Total uang lembur

            // Gaji Bersih
            $table->integer('net_salary'); // Gaji bersih (base - potongan + lembur)

            $table->date('payment_date')->nullable(); // Tanggal dibayar
            $table->enum('status', ['draft', 'paid'])->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Unique constraint: satu karyawan hanya bisa punya satu payroll per periode
            $table->unique(['employee_id', 'period_month', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
