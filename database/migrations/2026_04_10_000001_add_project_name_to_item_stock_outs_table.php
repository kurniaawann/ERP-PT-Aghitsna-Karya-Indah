<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->string('project_name')->nullable()->after('id_sales_recap');
        });
    }

    public function down(): void
    {
        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->dropColumn('project_name');
        });
    }
};