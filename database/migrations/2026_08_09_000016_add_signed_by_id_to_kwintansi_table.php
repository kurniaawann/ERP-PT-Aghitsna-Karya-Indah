<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom signed_by_id pada kwitansi.
 *
 * Kolom ini menyimpan penandatangan (petinggi/executive) untuk kwitansi
 * manual. Kwitansi otomatis dari bukti pembayaran juga diisi mengikuti
 * penandatangan invoice sumbernya.
 *
 * Backfill data lama: untuk setiap kwitansi ber-invoice, ambil signed_by_id
 * dari invoice sumber berdasarkan invoice_type (proyek/alumunium/barang).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->unsignedBigInteger('signed_by_id')->nullable()->after('payment_proof_id');
            $table->index('signed_by_id', 'idx_kwintansi_signed_by_id');
            $table->foreign('signed_by_id')->references('id')->on('executives')->nullOnDelete();
        });

        $invoiceTables = [
            'proyek' => 'proyek_invoices',
            'alumunium' => 'alumunium_invoices',
            'barang' => 'barang_invoices',
        ];

        foreach ($invoiceTables as $type => $invoiceTable) {
            DB::table('kwintansi')
                ->join($invoiceTable, $invoiceTable.'.invoice_number', '=', 'kwintansi.invoice_number')
                ->where('kwintansi.invoice_type', $type)
                ->whereNotNull($invoiceTable.'.signed_by_id')
                ->update(['kwintansi.signed_by_id' => DB::raw($invoiceTable.'.signed_by_id')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->dropForeign(['signed_by_id']);
            $table->dropIndex('idx_kwintansi_signed_by_id');
            $table->dropColumn('signed_by_id');
        });
    }
};
