<?php

use App\Models\Inventory\Cement;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Hapus kolom payment_account_id (rekening pembayaran) pada baris Data Semen.
 * Field "Rekening Pembayaran" tidak lagi digunakan pada modul DO Semen.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
            $table->dropColumn('payment_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_account_id')->nullable()->after('name');
            $table->foreign('payment_account_id')
                ->references('id')
                ->on('payment_accounts')
                ->onDelete('set null');
        });
    }
};