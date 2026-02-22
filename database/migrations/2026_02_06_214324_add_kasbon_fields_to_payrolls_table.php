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
        Schema::table('payrolls', function (Blueprint $table) {
            // Jenis periode: weekly (mingguan) - semua payroll mingguan untuk pekerja harian
            $table->enum('period_type', ['weekly'])->default('weekly')->after('period_year');

            // Nomor minggu (1-4) - wajib untuk payroll mingguan
            $table->integer('week_number')->default(1)->after('period_type');

            // Total kasbon yang dipotong dari gaji/upah (nullable karena opsional)
            $table->integer('kasbon_deduction')->nullable()->after('overtime_total');

            // Pengeluaran tambahan PT untuk karyawan (token listrik, air, dll) - nullable karena opsional
            $table->integer('additional_expenses')->nullable()->after('kasbon_deduction');

            // Catatan untuk pengeluaran tambahan
            $table->text('additional_expenses_notes')->nullable()->after('additional_expenses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'period_type',
                'week_number',
                'kasbon_deduction',
                'additional_expenses',
                'additional_expenses_notes'
            ]);
        });
    }
};
