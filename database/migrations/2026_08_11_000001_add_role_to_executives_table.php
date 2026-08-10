<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menambahkan kolom role pada tabel executives.
     *
     * role menentukan posisi petinggi pada dokumen tanda tangan payroll:
     * - disetujui  → kolom "Disetujui oleh"
     * - diperiksa  → kolom "Diperiksa oleh"
     * - dibuat     → kolom "Dibuat oleh"
     *
     * Nilai NULL berarti petinggi tidak dipakai pada blok tanda tangan.
     */
    public function up(): void
    {
        Schema::table('executives', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('position');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('executives', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
