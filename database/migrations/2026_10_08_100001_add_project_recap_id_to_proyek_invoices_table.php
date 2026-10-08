<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menautkan Invoice Proyek ke Rekap Proyek (opsional).
 *
 * Revisi klien: saat membuat/mengedit invoice proyek, user boleh memilih
 * Rekap Proyek yang sudah ada sehingga total tagihan per proyek bisa
 * dipantau (nilai proyek, sudah ditagih, sisa tagihan).
 *
 * - project_recap_id mengikuti tipe primary key project_recaps.id
 *   (string 20, format RP-00001).
 * - FK ON DELETE SET NULL: menghapus rekap tidak menghapus/memblokir
 *   invoice; tautannya saja yang dilepas.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->string('project_recap_id', 20)->nullable()->after('quotation_number');

            $table->foreign('project_recap_id', 'fk_proyek_invoices_project_recap_id')
                ->references('id')
                ->on('project_recaps')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropForeign('fk_proyek_invoices_project_recap_id');
            $table->dropColumn('project_recap_id');
        });
    }
};
