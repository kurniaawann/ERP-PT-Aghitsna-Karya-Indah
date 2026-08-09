<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom attachment dan location pada project_quotations.
 *
 * Field ini dipakai pada desain cetak (PDF/Excel) penawaran proyek
 * versi admin:
 * - attachment : isi lampiran pada surat penawaran (mis. "1 (satu) set")
 * - location   : lokasi pekerjaan pembangunan pada surat penawaran
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->string('attachment')->nullable()->after('subject');
            $table->string('location')->nullable()->after('project_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->dropColumn(['attachment', 'location']);
        });
    }
};
