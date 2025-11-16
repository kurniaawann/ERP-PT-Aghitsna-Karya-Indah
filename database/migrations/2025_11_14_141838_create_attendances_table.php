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
        Schema::create('attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'cuti', 'lembur']);

            // Untuk Lembur (opsional)
            $table->decimal('overtime_hours', 5, 2)->nullable(); // Jam lembur (misal: 2.5 jam)
            $table->integer('overtime_rate')->nullable(); // Upah per jam lembur
            $table->integer('overtime_total')->nullable(); // Total uang lembur (hours × rate)

            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: satu karyawan hanya bisa punya satu record per tanggal
            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
