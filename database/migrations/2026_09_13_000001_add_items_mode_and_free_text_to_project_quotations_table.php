<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom items_mode dan free_text pada project_quotations.
 *
 * Fitur opsi format isi penawaran (khusus role admin):
 * - items_mode : 'items' -> menampilkan tabel Item-Item Penawaran
 *                'text'  -> menampilkan blok teks/deskripsi saja
 * - free_text  : konten teks bebas yang ditampilkan saat items_mode = 'text'
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->string('items_mode')->default('items')->after('items');
            $table->text('free_text')->nullable()->after('items_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->dropColumn(['items_mode', 'free_text']);
        });
    }
};