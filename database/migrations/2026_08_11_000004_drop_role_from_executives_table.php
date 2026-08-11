<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus kolom role pada tabel executives.
 *
 * Fitur "Peran Tanda Tangan" di modul Data Petinggi dihapus. Kolom role
 * sebelumnya menentukan posisi petinggi pada blok tanda tangan payroll
 * (disetujui/diperiksa/dibuat); kini penanda tangan dipilih bebas per
 * proyek pada saat generate payroll dan disimpan sebagai snapshot.
 */
return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('executives', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('executives', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('position');
        });
    }
};
