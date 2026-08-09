<?php

use App\Models\Administrasi\Kwintansi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom selected_payment_accounts pada kwintansi.
 *
 * Kolom ini menyimpan daftar rekening pembayaran (JSON array id) untuk
 * kwitansi, mendukung banyak rekening seperti pada modul invoice.
 *
 * Backfill data lama: kwitansi yang sudah memiliki payment_account_id diisi
 * array [payment_account_id] dan include_bank diset true.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->json('selected_payment_accounts')->nullable()->after('payment_account_id');
        });

        Kwintansi::query()
            ->whereNotNull('payment_account_id')
            ->each(function (Kwintansi $kwintansi) {
                $kwintansi->selected_payment_accounts = [$kwintansi->payment_account_id];
                $kwintansi->include_bank = true;
                $kwintansi->save();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->dropColumn('selected_payment_accounts');
        });
    }
};
