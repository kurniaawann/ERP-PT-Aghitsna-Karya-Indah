<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom periode pada notas_administrasi:
 * - periode_start (date): awal periode pada cetakan nota (Periode : ... s/d ...)
 * - periode_end   (date): akhir periode pada cetakan nota
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->date('periode_start')->nullable()->after('nota_date');
            $table->date('periode_end')->nullable()->after('periode_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->dropColumn(['periode_start', 'periode_end']);
        });
    }
};
