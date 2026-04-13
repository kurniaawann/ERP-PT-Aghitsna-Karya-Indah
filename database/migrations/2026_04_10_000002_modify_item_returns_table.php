<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_returns', function (Blueprint $table) {
            // Add column untuk differentiate return masuk vs keluar
            $table->string('return_type')->default('keluar')->after('id_stock_out'); // 'masuk' atau 'keluar'
            $table->string('id_stock_in')->nullable()->after('return_type'); // Untuk return barang masuk

            // Add foreign key untuk id_stock_in
            $table->foreign('id_stock_in')->references('id_stock_in')->on('item_stock_ins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('item_returns', function (Blueprint $table) {
            $table->dropForeign(['id_stock_in']);
            $table->dropColumn(['return_type', 'id_stock_in']);
        });
    }
};
