<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus foreign key dulu
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        // Hapus unique constraint lama
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'period_month', 'period_year']);
        });

        // Tambah unique constraint baru yang include week_number
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_month', 'period_year', 'week_number'], 'payrolls_unique_constraint');
        });

        // Restore foreign key
        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus foreign key dulu
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        // Hapus unique constraint baru
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique('payrolls_unique_constraint');
        });

        // Kembalikan unique constraint lama
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_month', 'period_year']);
        });

        // Restore foreign key
        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
        });
    }
};
