<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom tanggal pembayaran (payment_date) pada payment_proofs.
 *
 * Memungkinkan pengguna mengisi/mengubah tanggal pembayaran secara manual
 * saat upload atau edit bukti pembayaran, tidak selalu otomatis dari created_at.
 * Data lama di-backfill dari tanggal created_at.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->after('created_by');
        });

        DB::table('payment_proofs')->whereNull('payment_date')->update([
            'payment_date' => DB::raw('DATE(created_at)'),
        ]);

        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->index('payment_date', 'idx_payment_proofs_payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropIndex('idx_payment_proofs_payment_date');
            $table->dropColumn('payment_date');
        });
    }
};
