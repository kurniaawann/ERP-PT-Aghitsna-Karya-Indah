<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices_administrasi', function (Blueprint $table) {
            // Field untuk menyimpan ID bank accounts yang dipilih
            $table->json('selected_payment_accounts')->nullable()->after('jumlah_total');

            // Field untuk PPN dengan default 12%
            $table->decimal('ppn_percentage', 5, 2)->default(12.00)->after('selected_payment_accounts');

            // Field untuk menyimpan nilai PPN dalam rupiah
            $table->integer('ppn_amount')->nullable()->after('ppn_percentage');

            // Field untuk total setelah PPN
            $table->integer('total_with_ppn')->nullable()->after('ppn_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices_administrasi', function (Blueprint $table) {
            $table->dropColumn([
                'selected_payment_accounts',
                'ppn_percentage',
                'ppn_amount',
                'total_with_ppn'
            ]);
        });
    }
};
