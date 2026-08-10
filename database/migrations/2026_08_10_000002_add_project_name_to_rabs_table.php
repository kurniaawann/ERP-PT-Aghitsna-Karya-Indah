<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tambahkan kolom project_name ke tabel rabs.
     *
     * RAB dikaitkan dengan nama proyek; saat RAB dibuat, Rekap Proyek
     * otomatis dibuat memakai nama proyek ini.
     */
    public function up(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            $table->string('project_name')->nullable()->after('rab_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            $table->dropColumn('project_name');
        });
    }
};
