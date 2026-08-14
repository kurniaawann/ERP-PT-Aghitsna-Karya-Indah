<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan kolom payment_account_id (rekening pembayaran) pada baris Data Semen.
 * Bersifat opsional (nullable) sehingga bisa dikosongkan saat input.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_account_id')->nullable()->after('name');
            $table->foreign('payment_account_id')
                ->references('id')
                ->on('payment_accounts')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
            $table->dropColumn('payment_account_id');
        });
    }
};
