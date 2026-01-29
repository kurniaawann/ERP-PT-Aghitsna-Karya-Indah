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
        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->enum('template_type', ['standard', 'hollow'])->default('standard')->after('finance_head');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->dropColumn('template_type');
        });
    }
};
