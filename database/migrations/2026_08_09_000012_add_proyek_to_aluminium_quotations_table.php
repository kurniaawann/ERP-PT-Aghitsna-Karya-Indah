<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom nama proyek pada aluminium_quotations.
 *
 * Dipakai pada kalimat pembuka penawaran PDF/Excel:
 * "Dengan ini kami sampaikan penawaran proyek {proyek}".
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aluminium_quotations', function (Blueprint $table) {
            $table->string('proyek')->nullable()->after('project_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aluminium_quotations', function (Blueprint $table) {
            $table->dropColumn('proyek');
        });
    }
};
