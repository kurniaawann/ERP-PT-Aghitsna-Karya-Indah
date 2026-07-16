<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Upah per hari untuk pekerja harian (semua pekerja adalah harian)
            // Nullable untuk backward compatibility dengan data existing
            $table->integer('daily_wage')->nullable()->after('base_salary');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('daily_wage');
        });
    }
};
