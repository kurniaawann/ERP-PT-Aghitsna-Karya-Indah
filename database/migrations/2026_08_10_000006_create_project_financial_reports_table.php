<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Satu Rekap Proyek memiliki satu Laporan Keuangan Proyek (relasi 1:1).
     * Laporan dibuat otomatis saat dibuka pertama kali dari tombol di Rekap
     * Proyek, jadi project_recap_id bersifat unique.
     */
    public function up(): void
    {
        Schema::create('project_financial_reports', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // Custom ID: LFP-00001, LFP-00002

            $table->string('project_recap_id', 20);
            $table->foreign('project_recap_id')->references('id')->on('project_recaps')->cascadeOnDelete();

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique('project_recap_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_financial_reports');
    }
};
