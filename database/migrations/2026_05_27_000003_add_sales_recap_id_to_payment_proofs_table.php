<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->string('sales_recap_id')->nullable()->after('invoice_number');
            $table->index('sales_recap_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropIndex(['sales_recap_id']);
            $table->dropColumn('sales_recap_id');
        });
    }
};