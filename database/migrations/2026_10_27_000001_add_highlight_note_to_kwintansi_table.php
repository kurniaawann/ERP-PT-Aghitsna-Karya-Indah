<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan "kolom kuning" pada PDF kwitansi bisa diatur user.
 *
 * Revisi klien: catatan kuning ("*Total Uang Masuk Per ... = Rp ...")
 * sebelumnya selalu otomatis. Sekarang pilihannya:
 * - auto   : teks otomatis Total Uang Masuk (perilaku lama, default)
 * - custom : teks ditulis sendiri (highlight_note)
 * - none   : tidak ditampilkan
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->string('highlight_mode', 10)->default('auto')->after('payment_for');
            $table->string('highlight_note')->nullable()->after('highlight_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->dropColumn(['highlight_mode', 'highlight_note']);
        });
    }
};
