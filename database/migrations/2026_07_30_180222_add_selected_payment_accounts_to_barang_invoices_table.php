<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->json('selected_payment_accounts')->nullable()->after('sales_recap_id');
        });
    }

    public function down(): void
    {
        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->dropColumn('selected_payment_accounts');
        });
    }
};
