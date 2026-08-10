<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat kolom jabatan (position) petinggi menjadi opsional.
 */
return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('executives', function (Blueprint $table) {
            $table->string('position', 150)->nullable()->change();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('executives', function (Blueprint $table) {
            $table->string('position', 150)->nullable(false)->change();
        });
    }
};
