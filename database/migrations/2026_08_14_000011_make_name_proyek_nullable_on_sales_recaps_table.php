<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat kolom name_proyek pada tabel sales_recaps menjadi nullable.
 *
 * Beberapa sumber data (mis. Invoice Barang / Invoice Semen) dapat berisi
 * tanpa nama proyek, sehingga kolom ini perlu diperbolehkan bernilai NULL.
 * Hanya kolom name_proyek yang dinullable, bukan seluruh tabel.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_recaps', function (Blueprint $table) {
            $table->string('name_proyek')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_recaps', function (Blueprint $table) {
            $table->string('name_proyek')->nullable(false)->change();
        });
    }
};