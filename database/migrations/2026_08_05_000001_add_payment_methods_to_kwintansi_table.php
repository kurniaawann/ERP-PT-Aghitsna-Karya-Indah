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
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->boolean('is_tunai')->default(true)->after('include_bank');
            $table->boolean('is_cheque')->default(false)->after('is_tunai');
            $table->boolean('is_bilyet_giro')->default(false)->after('is_cheque');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kwintansi', function (Blueprint $table) {
            $table->dropColumn(['is_tunai', 'is_cheque', 'is_bilyet_giro']);
        });
    }
};
