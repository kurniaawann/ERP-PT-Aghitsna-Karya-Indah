<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom invoice_type pada kwintansi.
 *
 * Kolom ini menandai asal invoice yang menjadi sumber kwitansi
 * (proyek | alumunium | barang | null untuk kwitansi manual),
 * sehingga design PDF dan relasi bisa membedakan sumber dengan benar.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->string('invoice_type', 50)->nullable()->after('invoice_number')->index();
        });

        DB::table('kwintansi')
            ->leftJoin('payment_proofs', 'kwintansi.payment_proof_id', '=', 'payment_proofs.id')
            ->whereNotNull('kwintansi.payment_proof_id')
            ->whereNotNull('payment_proofs.invoice_type')
            ->update(['kwintansi.invoice_type' => DB::raw('payment_proofs.invoice_type')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }
};
