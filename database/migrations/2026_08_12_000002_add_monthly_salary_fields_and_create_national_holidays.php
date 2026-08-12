<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * 1. Tambah kolom payroll bulanan pada employees:
     *    status (input bebas), uang transport, uang makan, UMP.
     * 2. Perluas salary_slips dengan snapshot tunjangan & rincian
     *    potongan perhitungan gaji bulanan (BPJS/JHT/JKK/JKM/JPN,
     *    PPh 21 manual, kasbon, THP).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'status')) {
                $table->string('status', 100)->nullable()->after('position');
            }
            if (! Schema::hasColumn('employees', 'transport_rate')) {
                $table->integer('transport_rate')->nullable()->after('base_salary');
            }
            if (! Schema::hasColumn('employees', 'meal_rate')) {
                $table->integer('meal_rate')->nullable()->after('transport_rate');
            }
            if (! Schema::hasColumn('employees', 'ump')) {
                $table->integer('ump')->nullable()->after('meal_rate');
            }
        });

        Schema::table('salary_slips', function (Blueprint $table) {
            // Snapshot tunjangan karyawan saat slip dibuat
            $table->integer('transport_rate')->default(0)->after('base_salary');
            $table->integer('meal_rate')->default(0)->after('transport_rate');
            $table->integer('ump')->default(0)->after('meal_rate');

            // Hari libur (Minggu + libur nasional) pada matriks absensi
            $table->integer('libur_days')->default(0)->after('absent_days');

            // Penerimaan
            $table->integer('transport_total')->default(0)->after('libur_days'); // transport_rate x hadir
            $table->integer('meal_total')->default(0)->after('transport_total'); // meal_rate x hadir
            $table->integer('total_income')->default(0)->after('meal_total'); // gaji pokok + transport + makan

            // Potongan (dibayar karyawan)
            $table->integer('bpjs_kesehatan_employee')->default(0)->after('total_income'); // 1% x gaji pokok
            $table->integer('jht_employee')->default(0)->after('bpjs_kesehatan_employee'); // 2% x UMP
            $table->integer('jpn_employee')->default(0)->after('jht_employee'); // 1% x UMP
            $table->integer('pph21')->default(0)->after('jpn_employee'); // input manual
            $table->integer('kasbon_deduction')->default(0)->after('pph21'); // otomatis dari kasbon pending
            $table->integer('total_deduction')->default(0)->after('kasbon_deduction');

            // Iuran dibayar perusahaan (informasi pada slip)
            $table->integer('bpjs_kesehatan_company')->default(0)->after('total_deduction'); // 4% x UMP
            $table->integer('jht_company')->default(0)->after('bpjs_kesehatan_company'); // 3,70% x UMP
            $table->integer('jkk_company')->default(0)->after('jht_company'); // 0,24% x UMP
            $table->integer('jkm_company')->default(0)->after('jkk_company'); // 0,30% x UMP
        });

        // Tabel national_holidays dihapus dari skema — hari libur dipilih
        // manual saat generate slip gaji. Lihat migrasi drop_national_holidays.
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            foreach ([
                'transport_rate', 'meal_rate', 'ump', 'libur_days',
                'transport_total', 'meal_total', 'total_income',
                'bpjs_kesehatan_employee', 'jht_employee', 'jpn_employee',
                'pph21', 'kasbon_deduction', 'total_deduction',
                'bpjs_kesehatan_company', 'jht_company', 'jkk_company', 'jkm_company',
            ] as $column) {
                if (Schema::hasColumn('salary_slips', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (['status', 'transport_rate', 'meal_rate', 'ump'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
