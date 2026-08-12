<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah tipe kolom created_by pada surat_perintah_kerjas menjadi UUID.
     *
     * Sebelumnya dibuat sebagai unsignedBigInteger, padahal primary key tabel
     * users adalah UUID sehingga penyimpanan auth()->id() gagal (SQLSTATE
     * 1366 Incorrect integer value). Kolom dibuat ulang sebagai uuid.
     */
    public function up(): void
    {
        Schema::table('surat_perintah_kerjas', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('surat_perintah_kerjas', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->after('total_amount');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surat_perintah_kerjas', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('surat_perintah_kerjas', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('total_amount');
        });
    }
};
