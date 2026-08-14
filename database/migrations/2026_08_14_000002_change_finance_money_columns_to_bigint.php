<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom uang modul Finance menjadi BIGINT sesuai best practice ERP.
 *
 * Kolom rupiah dinaikkan ke bigInteger agar tidak overflow pada nilai
 * > 2,14 M (batas INT), terutama total transaksi proyek yang besar.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->bigInteger('total_amount')->change();
            $table->bigInteger('total_after_discount')->nullable()->change();
            $table->bigInteger('dp_amount')->nullable()->change();
        });

        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->bigInteger('total_capital')->change();
            $table->bigInteger('total_selling')->change();
            $table->bigInteger('total_profit')->change();
        });

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->bigInteger('total_amount')->change();
            $table->bigInteger('total_after_discount')->nullable()->change();
            $table->bigInteger('dp_amount')->nullable()->change();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->bigInteger('selling_price')->change();
            $table->bigInteger('ppn_tax')->change();
        });

        Schema::table('reimburses', function (Blueprint $table) {
            $table->bigInteger('total_amount')->change();
        });

        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });

        Schema::table('expense_recaps', function (Blueprint $table) {
            $table->bigInteger('income_amount')->nullable()->change();
            $table->bigInteger('expense_amount')->nullable()->change();
        });

        Schema::table('sales_recaps', function (Blueprint $table) {
            $table->bigInteger('total_capital')->change();
            $table->bigInteger('total_selling')->change();
            $table->bigInteger('total_profit')->change();
        });

        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->bigInteger('sewa_jual')->nullable()->change();
            $table->bigInteger('ongkos_kirim')->nullable()->change();
            $table->bigInteger('bongkar_pasang')->nullable()->change();
            $table->bigInteger('lembur')->nullable()->change();
            $table->bigInteger('uang_jaminan')->nullable()->change();
            $table->bigInteger('jumlah_total')->change();
            $table->bigInteger('ppn_amount')->nullable()->change();
            $table->bigInteger('total_with_ppn')->nullable()->change();
        });

        Schema::table('kasbons', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });

        Schema::table('kasbon_payments', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });
    }

    public function down(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->integer('total_amount')->change();
            $table->integer('total_after_discount')->nullable()->change();
            $table->integer('dp_amount')->nullable()->change();
        });

        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->integer('total_capital')->change();
            $table->integer('total_selling')->change();
            $table->integer('total_profit')->change();
        });

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->integer('total_amount')->change();
            $table->integer('total_after_discount')->nullable()->change();
            $table->integer('dp_amount')->nullable()->change();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->integer('selling_price')->change();
            $table->integer('ppn_tax')->change();
        });

        Schema::table('reimburses', function (Blueprint $table) {
            $table->integer('total_amount')->change();
        });

        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->integer('amount')->change();
        });

        Schema::table('expense_recaps', function (Blueprint $table) {
            $table->integer('income_amount')->nullable()->change();
            $table->integer('expense_amount')->nullable()->change();
        });

        Schema::table('sales_recaps', function (Blueprint $table) {
            $table->integer('total_capital')->change();
            $table->integer('total_selling')->change();
            $table->integer('total_profit')->change();
        });

        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->integer('sewa_jual')->nullable()->change();
            $table->integer('ongkos_kirim')->nullable()->change();
            $table->integer('bongkar_pasang')->nullable()->change();
            $table->integer('lembur')->nullable()->change();
            $table->integer('uang_jaminan')->nullable()->change();
            $table->integer('jumlah_total')->change();
            $table->integer('ppn_amount')->nullable()->change();
            $table->integer('total_with_ppn')->nullable()->change();
        });

        Schema::table('kasbons', function (Blueprint $table) {
            $table->integer('amount')->change();
        });

        Schema::table('kasbon_payments', function (Blueprint $table) {
            $table->integer('amount')->change();
        });
    }
};