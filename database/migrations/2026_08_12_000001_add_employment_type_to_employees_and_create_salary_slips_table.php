<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     *
     * 1. Tambah kolom employment_type pada employees untuk membedakan pekerja
     *    harian (tukang, payroll mingguan) dan karyawan bulanan (slip gaji).
     * 2. Buat tabel salary_slips — satu baris per karyawan per bulan menyimpan
     *    snapshot slip gaji (rekap absensi 30 hari, perhitungan, tanda tangan).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'employment_type')) {
                $table->enum('employment_type', ['harian', 'bulanan'])
                    ->default('harian')
                    ->after('daily_wage');
            }
        });

        Schema::create('salary_slips', function (Blueprint $table) {
            $table->increments('id');
            $table->string('employee_code', 50);
            $table->foreign('employee_code')
                ->references('employee_code')
                ->on('employees')
                ->onDelete('cascade');

            // Periode slip (bulanan)
            $table->integer('period_month'); // Bulan (1-12)
            $table->integer('period_year'); // Tahun (2026)

            // Snapshot gaji pokok bulanan saat slip dibuat
            $table->integer('base_salary')->default(0);

            // Rekap absensi (dihitung dari attendance_detail)
            $table->integer('work_days')->default(0); // Total hari kalender periode
            $table->integer('present_days')->default(0); // Hadir
            $table->integer('permission_days')->default(0); // Izin
            $table->integer('sick_days')->default(0); // Sakit
            $table->integer('leave_days')->default(0); // Cuti
            $table->integer('absent_days')->default(0); // Alpha

            // Matriks absensi per tanggal (JSON: { "1": "H", "2": "I", ... })
            $table->json('attendance_detail')->nullable();

            // Perhitungan
            $table->integer('daily_rate')->default(0); // base_salary / work_days
            $table->integer('salary_deduction')->default(0); // daily_rate x (izin+sakit+cuti+alpha)
            $table->integer('net_salary')->default(0); // base_salary - salary_deduction

            $table->date('payment_date')->nullable();
            $table->enum('status', ['draft', 'paid'])->default('draft');
            $table->text('notes')->nullable();

            // Snapshot petinggi untuk blok tanda tangan (disetujui/diperiksa/dibuat)
            $table->json('signatures')->nullable();

            $table->string('created_by', 36)->nullable();
            $table->timestamps();

            // Satu karyawan hanya memiliki satu slip per periode per user
            $table->unique(
                ['employee_code', 'period_year', 'period_month', 'created_by'],
                'salary_slips_unique_period'
            );
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_slips');

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'employment_type')) {
                $table->dropColumn('employment_type');
            }
        });
    }
};
