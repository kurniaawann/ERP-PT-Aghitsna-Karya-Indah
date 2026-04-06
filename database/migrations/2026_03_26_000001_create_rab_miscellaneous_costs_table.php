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
        Schema::create('rab_miscellaneous_costs', function (Blueprint $table) {
            $table->id();
            $table->string('rab_number');
            $table->integer('item_order'); // 1, 2, 3, 4, etc
            $table->string('item_name');
            $table->bigInteger('amount')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('rab_number')->references('rab_number')->on('rabs')->onDelete('cascade');
            $table->unique(['rab_number', 'item_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rab_miscellaneous_costs');
    }
};
