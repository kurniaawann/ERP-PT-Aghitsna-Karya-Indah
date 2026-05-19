<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {
            $table->decimal('volume', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->bigInteger('unit_price')->nullable();
            $table->bigInteger('sub_harga')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {
            $table->dropColumn(['volume', 'unit', 'unit_price', 'sub_harga']);
        });
    }
};