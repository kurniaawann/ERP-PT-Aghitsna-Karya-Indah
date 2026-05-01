<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Mengubah status SalaryReminder untuk mengikuti prinsip payroll:
     * - 'pending' dan 'notified' menjadi 'draft' (belum dibayar)
     * - 'paid' tetap 'paid' (sudah dibayar)
     */
    public function up(): void
    {
        // Convert data lama ke status baru SEBELUM mengubah column definition
        // Mapping: pending -> draft, notified -> draft, paid -> paid
        DB::update("UPDATE salary_reminders SET status = 'draft' WHERE status IN ('pending', 'notified')");

        // Ubah column enum ke status baru yang hanya ada 'draft' dan 'paid'
        Schema::table('salary_reminders', function (Blueprint $table) {
            // Update column definition menggunakan raw SQL untuk enum
            DB::statement("ALTER TABLE salary_reminders MODIFY status ENUM('draft', 'paid') DEFAULT 'draft'");
        });
    }

    /**
     * Reverse the migrations.
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
