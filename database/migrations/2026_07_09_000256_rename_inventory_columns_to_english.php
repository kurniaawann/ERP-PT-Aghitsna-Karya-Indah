<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_stock_ins', function (Blueprint $table) {
            $table->renameColumn('keterangan', 'notes');
            $table->renameColumn('tanggal', 'date');
        });

        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->renameColumn('tanggal', 'date');
        });

        Schema::table('item_returns', function (Blueprint $table) {
            $table->renameColumn('alasan', 'reason');
            $table->renameColumn('keterangan', 'notes');
            $table->renameColumn('tanggal', 'date');
        });
    }

    public function down(): void
    {
        Schema::table('item_returns', function (Blueprint $table) {
            $table->renameColumn('reason', 'alasan');
            $table->renameColumn('notes', 'keterangan');
            $table->renameColumn('date', 'tanggal');
        });

        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->renameColumn('date', 'tanggal');
        });

        Schema::table('item_stock_ins', function (Blueprint $table) {
            $table->renameColumn('notes', 'keterangan');
            $table->renameColumn('date', 'tanggal');
        });
    }
};
