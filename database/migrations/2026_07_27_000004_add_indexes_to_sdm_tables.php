<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // attendances — 2 index
        // ═══════════════════════════════════════════════════════════════
        // idx_att_created_by
        // Digunakan oleh: AttendanceService, OvertimeService
        //   → WHERE created_by = auth()->id() di SETIAP request
        // Alasan: created_by adalah base filter di setiap listing absensi.
        //   UNIQUE constraint (employee_id, attendance_date) tidak membantu
        //   karena MySQL tidak bisa skip ke kolom ketiga (created_by).
        //
        // idx_att_created_by_date
        // Digunakan oleh: AttendanceService, OvertimeService
        //   → WHERE created_by = X ORDER BY attendance_date DESC
        // Alasan: Composite index menutupi pola query paling umum:
        //   filter per user + sort by date. MySQL bisa langsung jumpa
        //   ke partition data user, lalu ambil data terbaru tanpa filesort.
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('created_by', 'idx_att_created_by');
            $table->index(['created_by', 'attendance_date'], 'idx_att_created_by_date');
        });

        // ═══════════════════════════════════════════════════════════════
        // payrolls — 3 index (maksimal)
        // ═══════════════════════════════════════════════════════════════
        // idx_pay_created_by
        // Digunakan oleh: PayrollService → WHERE created_by = auth()->id()
        //   di SEMUA query (listing, export, bulkPay, deleteDraft)
        // Alasan: created_by adalah base filter. UNIQUE constraint
        //   (employee_id, period_start_date, created_by) tidak optimal
        //   untuk query yang filter created_by duluan.
        //
        // idx_pay_created_by_date
        // Digunakan oleh: PayrollService → WHERE created_by + whereMonth/Year('period_start_date')
        //   + latest('period_start_date')
        // Alasan: Composite index menutupi pola query paling umum:
        //   filter user + filter bulan/tahun + sort by date.
        //
        // idx_pay_status
        // Digunakan oleh: PayrollService → WHERE status = 'draft' di
        //   bulkPayPayrolls() dan deleteDraftPayrolls()
        // Alasan: Status adalah filter kedua yang paling sering dipakai.
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index('created_by', 'idx_pay_created_by');
            $table->index(['created_by', 'period_start_date'], 'idx_pay_created_by_date');
            $table->index('status', 'idx_pay_status');
        });

        // ═══════════════════════════════════════════════════════════════
        // kasbons — 3 index (maksimal)
        // ═══════════════════════════════════════════════════════════════
        // idx_kas_created_by
        // Digunakan oleh: KasbonService → WHERE created_by = auth()->id()
        //   di SEMUA query
        // Alasan: Base filter di setiap request kasbon.
        //
        // idx_kas_created_by_date
        // Digunakan oleh: KasbonService → WHERE created_by + whereMonth/Year('period_start_date')
        //   + latest('kasbon_date')
        // Alasan: Composite untuk pola filter user + filter periode.
        //
        // idx_kas_status_payment
        // Digunakan oleh: KasbonService → WHERE status = 'pending' AND payment_status != 'paid'
        //   di aggregate queries (getTotalForEmployee, getTotalTeamKasbon,
        //   getPendingKasbonsForPeriod, getTotalRemainingForEmployee)
        // Alasan: Composite index untuk kombinasi filter status + payment_status
        //   yang dipakai di hampir semua kalkulasi kasbon.
        Schema::table('kasbons', function (Blueprint $table) {
            $table->index('created_by', 'idx_kas_created_by');
            $table->index(['created_by', 'period_start_date'], 'idx_kas_created_by_date');
            $table->index(['status', 'payment_status'], 'idx_kas_status_payment');
        });

        // ═══════════════════════════════════════════════════════════════
        // employees — 1 index
        // ═══════════════════════════════════════════════════════════════
        // idx_emp_division
        // Digunakan oleh: DivisionService → Division::withCount('employees')
        //   → WHERE employees.division = divisions.name (string-based join)
        //   Juga dipakai di KasbonService untuk team kasbon calculations.
        // Alasan: Kolom `division` adalah string FK ke `divisions.name`.
        //   Tanpa index, setiap withCount('employees') harus full scan
        //   tabel employees untuk mencocokkan nama divisi.
        Schema::table('employees', function (Blueprint $table) {
            $table->index('division', 'idx_emp_division');
        });

        // ═══════════════════════════════════════════════════════════════
        // salary_reminders — 1 index
        // ═══════════════════════════════════════════════════════════════
        // idx_sal_period
        // Digunakan oleh: SalaryReminderService → scopeForPeriod()
        //   → WHERE period_month = X AND period_year = Y
        //   + scopeByStatus() → WHERE status = Z
        // Alasan: Composite index untuk kombinasi filter bulan + tahun
        //   yang dipakai di setiap request listing salary reminder.
        Schema::table('salary_reminders', function (Blueprint $table) {
            $table->index(['period_month', 'period_year'], 'idx_sal_period');
        });

        // ═══════════════════════════════════════════════════════════════
        // kasbon_payments — 1 index
        // ═══════════════════════════════════════════════════════════════
        // idx_kp_kasbon_date
        // Digunakan oleh: KasbonService → getPayments()
        //   → WHERE kasbon_code = X ORDER BY payment_date DESC
        // Alasan: FK index pada kasbon_code sudah ada, tapi tanpa
        //   composite dengan payment_date, MySQL harus filesort
        //   untuk setiap riwayat pembayaran kasbon.
        Schema::table('kasbon_payments', function (Blueprint $table) {
            $table->index(['kasbon_code', 'payment_date'], 'idx_kp_kasbon_date');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_created_by');
            $table->dropIndex('idx_att_created_by_date');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('idx_pay_created_by');
            $table->dropIndex('idx_pay_created_by_date');
            $table->dropIndex('idx_pay_status');
        });

        Schema::table('kasbons', function (Blueprint $table) {
            $table->dropIndex('idx_kas_created_by');
            $table->dropIndex('idx_kas_created_by_date');
            $table->dropIndex('idx_kas_status_payment');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_emp_division');
        });

        Schema::table('salary_reminders', function (Blueprint $table) {
            $table->dropIndex('idx_sal_period');
        });

        Schema::table('kasbon_payments', function (Blueprint $table) {
            $table->dropIndex('idx_kp_kasbon_date');
        });
    }
};
