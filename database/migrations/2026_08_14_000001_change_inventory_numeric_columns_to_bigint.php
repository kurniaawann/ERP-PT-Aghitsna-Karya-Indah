<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom numerik modul Inventory menjadi BIGINT sesuai best practice ERP.
 *
 * Kolom uang (rupiah) dan saldo stok kumulatif dinaikkan ke bigInteger agar
 * tidak overflow pada nilai > 2,14 M (batas INT) maupun saat agregasi/perkalian
 * (quantity x harga).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
            $table->bigInteger('capital_price')->default(0)->change();
            $table->bigInteger('selling_price')->default(0)->change();
        });

        Schema::table('item_stock_ins', function (Blueprint $table) {
            $table->bigInteger('quantity')->change();
            $table->bigInteger('capital_price')->change();
        });

        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->bigInteger('quantity')->change();
        });

        Schema::table('item_returns', function (Blueprint $table) {
            $table->bigInteger('quantity')->change();
        });

        Schema::table('cements', function (Blueprint $table) {
            $table->bigInteger('jumlah')->default(0)->change();
            $table->bigInteger('harga')->default(0)->change();
        });

        Schema::table('cement_delivery_orders', function (Blueprint $table) {
            $table->bigInteger('harga_modal')->default(0)->change();
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->bigInteger('total_quantity')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
            $table->integer('capital_price')->default(0)->change();
            $table->integer('selling_price')->default(0)->change();
        });

        Schema::table('item_stock_ins', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('capital_price')->change();
        });

        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('item_returns', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('cements', function (Blueprint $table) {
            $table->integer('jumlah')->default(0)->change();
            $table->integer('harga')->default(0)->change();
        });

        Schema::table('cement_delivery_orders', function (Blueprint $table) {
            $table->integer('harga_modal')->default(0)->change();
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->integer('total_quantity')->default(0)->change();
        });
    }
};