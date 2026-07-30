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
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->string('signed_by')->nullable()->after('selected_payment_accounts');
            $table->string('division')->nullable()->after('signed_by');
        });

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->string('signed_by')->nullable()->after('selected_payment_accounts');
            $table->string('division')->nullable()->after('signed_by');
        });

        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->string('signed_by')->nullable()->after('selected_payment_accounts');
            $table->string('division')->nullable()->after('signed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->dropColumn(['signed_by', 'division']);
        });

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropColumn(['signed_by', 'division']);
        });

        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->dropColumn(['signed_by', 'division']);
        });
    }
};
