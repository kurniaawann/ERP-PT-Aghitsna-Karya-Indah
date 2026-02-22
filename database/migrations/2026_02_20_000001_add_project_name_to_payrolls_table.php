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
            // Nama proyek untuk payroll ini (diisi manual saat generate)
            // Contoh: "Lampsr Tanap 3", "Proyek ABC", dll
            $table->string('project_name', 255)->nullable()->after('week_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('project_name');
        });
    }
};
