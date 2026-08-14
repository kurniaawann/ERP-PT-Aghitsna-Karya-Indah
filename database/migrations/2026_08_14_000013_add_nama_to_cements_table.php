<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan kolom nama (nama pemesan/customer) pada baris Data Semen.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->string('name')->nullable()->after('nama_proyek');
        });
    }

    public function down(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->dropColumn('nama');
        });
    }
};
