<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tambahkan kolom rab_number ke tabel project_recaps.
     *
     * Menautkan Rekap Proyek ke RAB sumbernya. Saat RAB dibuat/diupdate/
     * dihapus, rekap terkait ikut disinkronkan/dihapus.
     */
    public function up(): void
    {
        Schema::table('project_recaps', function (Blueprint $table) {
            $table->string('rab_number')->nullable()->after('project_name');

            $table->foreign('rab_number')
                ->references('rab_number')
                ->on('rabs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_recaps', function (Blueprint $table) {
            $table->dropForeign(['rab_number']);
            $table->dropColumn('rab_number');
        });
    }
};
