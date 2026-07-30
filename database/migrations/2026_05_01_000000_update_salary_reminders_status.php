<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     * 
     * Mengubah status PengingatGaji (SalaryReminder) untuk mengikuti prinsip payroll:
     * - 'pending' dan 'notified' menjadi 'draft' (belum dibayar)
     * - 'paid' tetap 'paid' (sudah dibayar)
     */
    public function up(): void
    {
        // Konversi data lama ke status baru SEBELUM mengubah definisi kolom
        // Pemetaan: pending -> draft, notified -> draft, paid -> paid
        DB::update("UPDATE salary_reminders SET status = 'draft' WHERE status IN ('pending', 'notified')");

        // Ubah kolom enum ke status baru yang hanya berisi 'draft' dan 'paid'
        Schema::table('salary_reminders', function (Blueprint $table) {
            // Perbarui definisi kolom menggunakan SQL mentah untuk enum
            DB::statement("ALTER TABLE salary_reminders MODIFY status ENUM('draft', 'paid') DEFAULT 'draft'");
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('salary_reminders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('salary_reminders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'notified', 'paid'])->default('pending')->after('reminder_date');
        });
    }
};
