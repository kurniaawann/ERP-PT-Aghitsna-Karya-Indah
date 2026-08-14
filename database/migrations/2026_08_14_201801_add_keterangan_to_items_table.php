<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom keterangan (opsional) pada tabel items.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('keterangan', 255)->nullable()->after('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};