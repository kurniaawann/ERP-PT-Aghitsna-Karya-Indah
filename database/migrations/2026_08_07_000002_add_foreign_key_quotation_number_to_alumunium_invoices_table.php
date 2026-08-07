<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menambahkan foreign key constraint antara invoice alumunium dan
     * penawaran aluminium dengan ON DELETE SET NULL.
     *
     * Alasan SET NULL (bukan CASCADE/RESTRICT):
     * - Design snapshot: invoice yang dibuat otomatis dari penawaran TIDAK
     *   boleh terhapus ikut (CASCADE) dan hapus penawaran tidak boleh diblokir
     *   (RESTRICT).
     * - Saat penawaran dihapus, kolom quotation_number pada invoice di-set
     *   NULL sehingga tidak ada referensi menggantung (orphan).
     */
    public function up(): void
    {
        // Bersihkan referensi menggantung sebelum menambah FK (jika ada)
        DB::table('alumunium_invoices')
            ->whereNotNull('quotation_number')
            ->whereNotIn('quotation_number', DB::table('aluminium_quotations')->select('quotation_number'))
            ->update(['quotation_number' => null]);

        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->foreign('quotation_number', 'fk_alumunium_invoices_quotation_number')
                ->references('quotation_number')
                ->on('aluminium_quotations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->dropForeign('fk_alumunium_invoices_quotation_number');
        });
    }
};
