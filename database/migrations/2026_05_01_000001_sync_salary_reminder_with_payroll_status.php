<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     * 
     * Sinkronisasi status PengingatGaji (SalaryReminder) berdasarkan status Payroll yang terkait
     */
    public function up(): void
    {
        // Perbarui PengingatGaji yang memiliki payroll_id dengan status 'paid'
        // Jadikan status PengingatGaji juga 'paid'
        DB::update("
            UPDATE salary_reminders sr
            INNER JOIN payrolls p ON sr.payroll_id = p.id
            SET sr.status = 'paid',
                sr.notification_sent_at = NOW()
            WHERE p.status = 'paid' AND sr.status != 'paid'
        ");
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        // Kembalikan status yang telah disinkronkan, namun hanya yang memiliki payroll_id
        DB::update("
            UPDATE salary_reminders sr
            INNER JOIN payrolls p ON sr.payroll_id = p.id
            SET sr.status = 'draft',
                sr.notification_sent_at = NULL
            WHERE p.status = 'paid' AND sr.status = 'paid'
        ");
    }
};
