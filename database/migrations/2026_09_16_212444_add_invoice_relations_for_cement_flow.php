<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Relasi baru untuk alur "Buat Invoice Semen dari DO Semen":
     * - semen_invoices.do_no           : menautkan invoice ke DO (header).
     * - notas_administrasi.invoice_number : menautkan nota proyek ke invoice.
     * - notas_administrasi.do_no       : menautkan nota proyek ke DO.
     * - cements.invoice_number         : penanda data semen sudah diinvois
     *                                    (mencegah dobel).
     */
    public function up(): void
    {
        Schema::table('semen_invoices', function (Blueprint $table) {
            $table->string('do_no')->nullable()->after('invoice_number');
            $table->index('do_no', 'semen_invoices_do_no_idx');
        });

        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('id_nota');
            $table->string('do_no')->nullable()->after('invoice_number');
            $table->index('invoice_number', 'notas_invoice_number_idx');
            $table->index('do_no', 'notas_do_no_idx');
        });

        Schema::table('cements', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('tanggal_lunas');
            $table->index('invoice_number', 'cements_invoice_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->dropIndex('cements_invoice_number_idx');
            $table->dropColumn('invoice_number');
        });

        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->dropIndex('notas_invoice_number_idx');
            $table->dropIndex('notas_do_no_idx');
            $table->dropColumn(['do_no', 'invoice_number']);
        });

        Schema::table('semen_invoices', function (Blueprint $table) {
            $table->dropIndex('semen_invoices_do_no_idx');
            $table->dropColumn('do_no');
        });
    }
};
