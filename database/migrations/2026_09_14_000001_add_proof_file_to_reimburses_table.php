<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     *
     * Kolom ini menyimpan lampiran bukti reimburse (opsional).
     * proof_file  = path relatif file di Storage::disk('public')
     * proof_file_name = nama file asli saat diupload
     */
    public function up(): void
    {
        Schema::table('reimburses', function (Blueprint $table) {
            $table->string('proof_file')->nullable()->after('notes');
            $table->string('proof_file_name')->nullable()->after('proof_file');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('reimburses', function (Blueprint $table) {
            $table->dropColumn(['proof_file', 'proof_file_name']);
        });
    }
};