<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom reimburse_seen_at ke tabel users.
     *
     * Kolom ini mencatat kapan user terakhir membuka halaman Reimbursement,
     * dipakai untuk badge notifikasi di sidebar:
     * - Admin      : badge muncul saat ada pengajuan baru (draft) setelah
     *                terakhir dibuka.
     * - Super Admin: badge muncul saat ada perubahan status (disetujui/
     *                ditolak) setelah terakhir dibuka.
     * Saat menu Reimbursement diklik (halaman dibuka), badge hilang.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('reimburse_seen_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reimburse_seen_at');
        });
    }
};
