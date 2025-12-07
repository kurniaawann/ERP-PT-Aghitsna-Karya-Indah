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
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->json('selected_payment_accounts')->nullable()->after('dp_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->dropColumn('selected_payment_accounts');
        });
    }
};
