<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom uang modul Human Resource (HR) menjadi BIGINT sesuai best
 * practice ERP.
 *
 * Kolom rupiah (gaji, upah, tunjangan, potongan, iuran) dinaikkan ke
 * bigInteger agar tidak overflow pada nilai > 2,14 M (batas INT) maupun
 * saat perkalian/agregasi. Kolom hitungan (hari/bulan/minggu) tetap integer.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->bigInteger('base_salary')->change();
            $table->bigInteger('transport_rate')->nullable()->change();
            $table->bigInteger('meal_rate')->nullable()->change();
            $table->bigInteger('ump')->nullable()->change();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->bigInteger('base_salary')->change();
            $table->bigInteger('deduction_amount')->default(0)->change();
            $table->bigInteger('overtime_total')->default(0)->change();
            $table->bigInteger('net_salary')->change();
            $table->bigInteger('kasbon_deduction')->nullable()->change();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->bigInteger('overtime_rate')->nullable()->change();
            $table->bigInteger('overtime_total')->nullable()->change();
        });

        Schema::table('kasbons', function (Blueprint $table) {
            $table->bigInteger('paid_amount')->default(0)->change();
            $table->bigInteger('remaining_amount')->default(0)->change();
        });

        Schema::table('salary_slips', function (Blueprint $table) {
            $table->bigInteger('base_salary')->default(0)->change();
            $table->bigInteger('daily_rate')->default(0)->change();
            $table->bigInteger('salary_deduction')->default(0)->change();
            $table->bigInteger('net_salary')->default(0)->change();

            $table->bigInteger('transport_rate')->default(0)->change();
            $table->bigInteger('meal_rate')->default(0)->change();
            $table->bigInteger('ump')->default(0)->change();

            $table->bigInteger('transport_total')->default(0)->change();
            $table->bigInteger('meal_total')->default(0)->change();
            $table->bigInteger('total_income')->default(0)->change();

            $table->bigInteger('bpjs_kesehatan_employee')->default(0)->change();
            $table->bigInteger('jht_employee')->default(0)->change();
            $table->bigInteger('jpn_employee')->default(0)->change();
            $table->bigInteger('pph21')->default(0)->change();
            $table->bigInteger('kasbon_deduction')->default(0)->change();
            $table->bigInteger('total_deduction')->default(0)->change();

            $table->bigInteger('bpjs_kesehatan_company')->default(0)->change();
            $table->bigInteger('jht_company')->default(0)->change();
            $table->bigInteger('jkk_company')->default(0)->change();
            $table->bigInteger('jkm_company')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('base_salary')->change();
            $table->integer('transport_rate')->nullable()->change();
            $table->integer('meal_rate')->nullable()->change();
            $table->integer('ump')->nullable()->change();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('base_salary')->change();
            $table->integer('deduction_amount')->default(0)->change();
            $table->integer('overtime_total')->default(0)->change();
            $table->integer('net_salary')->change();
            $table->integer('kasbon_deduction')->nullable()->change();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('overtime_rate')->nullable()->change();
            $table->integer('overtime_total')->nullable()->change();
        });

        Schema::table('kasbons', function (Blueprint $table) {
            $table->integer('paid_amount')->default(0)->change();
            $table->integer('remaining_amount')->default(0)->change();
        });

        Schema::table('salary_slips', function (Blueprint $table) {
            $table->integer('base_salary')->default(0)->change();
            $table->integer('daily_rate')->default(0)->change();
            $table->integer('salary_deduction')->default(0)->change();
            $table->integer('net_salary')->default(0)->change();

            $table->integer('transport_rate')->default(0)->change();
            $table->integer('meal_rate')->default(0)->change();
            $table->integer('ump')->default(0)->change();

            $table->integer('transport_total')->default(0)->change();
            $table->integer('meal_total')->default(0)->change();
            $table->integer('total_income')->default(0)->change();

            $table->integer('bpjs_kesehatan_employee')->default(0)->change();
            $table->integer('jht_employee')->default(0)->change();
            $table->integer('jpn_employee')->default(0)->change();
            $table->integer('pph21')->default(0)->change();
            $table->integer('kasbon_deduction')->default(0)->change();
            $table->integer('total_deduction')->default(0)->change();

            $table->integer('bpjs_kesehatan_company')->default(0)->change();
            $table->integer('jht_company')->default(0)->change();
            $table->integer('jkk_company')->default(0)->change();
            $table->integer('jkm_company')->default(0)->change();
        });
    }
};