<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tambahkan kolom location ke tabel project_recaps.
     *
     * Lokasi proyek dipakai untuk keperluan tampilan PDF Laporan Keuangan Proyek.
     */
    public function up(): void
    {
        Schema::table('project_recaps', function (Blueprint $table) {
            $table->string('location', 255)->nullable()->after('project_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_recaps', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
