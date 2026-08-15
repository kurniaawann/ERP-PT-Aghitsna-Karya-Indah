<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom note (opsional) pada tabel semen_invoices.
 *
 * Catatan tambahan (NB) pada invoice semen. Kolom nullable sehingga
 * invoice tanpa catatan tidak menampilkan apa pun pada cetakan.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('semen_invoices', function (Blueprint $table) {
            $table->string('note')->nullable()->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semen_invoices', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};