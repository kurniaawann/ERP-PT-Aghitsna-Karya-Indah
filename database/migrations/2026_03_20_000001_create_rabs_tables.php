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
        // RAB Header Table
        Schema::create('rabs', function (Blueprint $table) {
            $table->string('rab_number')->primary();
            $table->integer('sequence_number')->unique();
            $table->date('date');
            $table->string('recipient');
            $table->text('recipient_address')->nullable();
            $table->text('intro_text')->nullable();
            $table->longText('selected_payment_accounts')->nullable();
            $table->string('signed_by')->nullable();
            $table->string('division')->nullable();
            $table->bigInteger('total_amount')->default(0);
            $table->string('amount_in_words')->nullable();
            $table->timestamps();
        });

        // RAB Categories (Roman numerals: I, II, III, etc)
        Schema::create('rab_categories', function (Blueprint $table) {
            $table->id();
            $table->string('rab_number');
            $table->integer('roman_order'); // 1, 2, 3, etc (for I, II, III, etc)
            $table->string('category_name');
            $table->bigInteger('category_subtotal')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('rab_number')->references('rab_number')->on('rabs')->onDelete('cascade');
            $table->unique(['rab_number', 'roman_order']);
        });

        // RAB Subcategories (Numbers: 1, 2, 3, etc)
        Schema::create('rab_subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rab_category_id');
            $table->integer('number_order'); // 1, 2, 3, etc
            $table->string('subcategory_name');
            $table->bigInteger('volume');
            $table->string('unit');
            $table->bigInteger('unit_price');
            $table->bigInteger('sub_harga')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('rab_category_id')->references('id')->on('rab_categories')->onDelete('cascade');
            $table->unique(['rab_category_id', 'number_order']);
        });

        // RAB Items (Letters: a, b, c, d, etc)
        Schema::create('rab_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rab_subcategory_id');
            $table->integer('letter_order'); // 1, 2, 3, etc (for a, b, c, etc)
            $table->string('item_description');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('rab_subcategory_id')->references('id')->on('rab_subcategories')->onDelete('cascade');
            $table->unique(['rab_subcategory_id', 'letter_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rab_items');
        Schema::dropIfExists('rab_subcategories');
        Schema::dropIfExists('rab_categories');
        Schema::dropIfExists('rabs');
    }
};
