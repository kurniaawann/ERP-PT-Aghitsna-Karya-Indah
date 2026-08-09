<?php

use App\Models\Administrasi\Kwintansi;
use App\Models\Finance\PaymentProof;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menautkan kwitansi otomatis dengan bukti pembayaran sumbernya.
 *
 * Kolom payment_proof_id menyimpan referensi ke PaymentProof yang memicu
 * auto-generate kwitansi, sehingga tanggal kwitansi bisa ikut disesuaikan
 * saat tanggal pembayaran (payment_date) diubah manual.
 *
 * Backfill data lama: untuk setiap kwitansi ber-invoice, cari payment proof
 * invoice proyek dengan nomor invoice, nominal, dan tanggal yang cocok, lalu
 * tautkan dan sesuaikan kwintansi_date dari payment_date bukti tersebut.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_proof_id')->nullable()->after('created_by');
            $table->index('payment_proof_id', 'idx_kwintansi_payment_proof_id');
        });

        $usedProofIds = [];

        Kwintansi::query()
            ->whereNotNull('invoice_number')
            ->whereNull('payment_proof_id')
            ->orderBy('created_at')
            ->get()
            ->each(function (Kwintansi $kwintansi) use (&$usedProofIds) {
                $proof = PaymentProof::query()
                    ->where('invoice_type', 'proyek')
                    ->where('invoice_number', $kwintansi->invoice_number)
                    ->where('amount', (float) $kwintansi->amount)
                    ->whereDate('created_at', '<=', $kwintansi->kwintansi_date->toDateString())
                    ->whereNotIn('id', $usedProofIds)
                    ->orderBy('created_at')
                    ->first();

                if (!$proof) {
                    return;
                }

                $usedProofIds[] = $proof->id;

                $kwintansi->payment_proof_id = $proof->id;
                $kwintansi->kwintansi_date = $proof->payment_date?->toDateString() ?? $kwintansi->kwintansi_date->toDateString();
                $kwintansi->save();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->dropIndex('idx_kwintansi_payment_proof_id');
            $table->dropColumn('payment_proof_id');
        });
    }
};
