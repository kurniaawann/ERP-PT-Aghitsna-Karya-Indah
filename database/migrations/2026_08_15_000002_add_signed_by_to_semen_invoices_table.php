<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom signed_by_id pada tabel semen_invoices.
 *
 * Nama penandatangan invoice diambil dari Data Petinggi (executives)
 * melalui foreign key signed_by_id (nullable).
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('semen_invoices', function (Blueprint $table) {
            $table->foreignId('signed_by_id')
                ->nullable()
                ->after('total_amount')
                ->constrained('executives')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semen_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by_id');
        });
    }
};