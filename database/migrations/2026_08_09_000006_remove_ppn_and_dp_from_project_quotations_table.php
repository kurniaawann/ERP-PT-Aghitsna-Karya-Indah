<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus kolom PPN dan DP dari project_quotations.
 *
 * Design separasi ketat (best practice):
 * - project_quotations hanya menyimpan kebutuhan PDF penawaran.
 * - PPN & DP adalah konsep penagihan/pembayaran, hanya milik proyek_invoices.
 * - Invoice yang dibuat dari penawaran tidak lagi menyalin PPN/DP dari
 *   penawaran; keduanya diisi langsung pada form invoice (modul Finance).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->dropColumn(['ppn', 'dp_type', 'dp_value', 'dp_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->decimal('ppn', 5, 2)->nullable()->after('discount_value');
            $table->string('dp_type')->nullable()->after('total_after_discount');
            $table->decimal('dp_value', 15, 2)->nullable()->after('dp_type');
            $table->integer('dp_amount')->nullable()->after('dp_value');
        });
    }
};
