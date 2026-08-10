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
            // Nama proyek untuk karyawan (opsional) — diambil dari Rekap Proyek
            $table->string('project_name', 255)->nullable()->after('division');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('project_name');
        });
    }
};
