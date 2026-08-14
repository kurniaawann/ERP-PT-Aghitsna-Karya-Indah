<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat kolom faktur_no dan sj_no nullable.
 *
 * Nota tipe 'proyek' tidak menggunakan faktur/sj, sehingga kolom
 * ini boleh bernilai NULL.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->string('faktur_no')->nullable()->change();
            $table->string('sj_no')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->string('faktur_no')->nullable(false)->change();
            $table->string('sj_no')->nullable(false)->change();
        });
    }
};
