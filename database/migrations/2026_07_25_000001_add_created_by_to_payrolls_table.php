<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->after('updated_at');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Ubah unique constraint: (employee_id, period_start_date) -> (employee_id, period_start_date, created_by)
        // Agar karyawan yang sama bisa punya payroll dari admin DAN superadmin secara terpisah
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique('payrolls_unique_period');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_start_date', 'created_by'], 'payrolls_unique_period');
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique('payrolls_unique_period');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_start_date'], 'payrolls_unique_period');
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
