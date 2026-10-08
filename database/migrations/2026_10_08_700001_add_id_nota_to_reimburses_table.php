<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     *
     * Revisi klien: setiap Nota yang dibuat Super Admin (termasuk nota proyek
     * otomatis dari Invoice Semen) otomatis membuat pengajuan Reimbursement
     * berstatus draft. Kolom id_nota menautkan reimburse ke nota sumbernya.
     *
     * - nullable : reimburse manual tidak punya nota sumber.
     * - unique   : satu nota maksimal satu reimburse (mencegah duplikat).
     * - FK ON DELETE SET NULL : reimburse yang sudah disetujui/ditolak tetap
     *   tersimpan saat notanya dihapus (tautan saja yang dikosongkan). Draft
     *   milik nota yang dihapus dibersihkan oleh aplikasi (NotaBuilder).
     */
    public function up(): void
    {
        Schema::table('reimburses', function (Blueprint $table) {
            $table->string('id_nota')->nullable()->after('proof_file_name');
            $table->unique('id_nota', 'reimburses_id_nota_unique');
            $table->foreign('id_nota', 'reimburses_id_nota_foreign')
                ->references('id_nota')
                ->on('notas_administrasi')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('reimburses', function (Blueprint $table) {
            $table->dropForeign('reimburses_id_nota_foreign');
            $table->dropUnique('reimburses_id_nota_unique');
            $table->dropColumn('id_nota');
        });
    }
};
