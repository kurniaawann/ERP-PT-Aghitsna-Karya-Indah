<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tabel biaya lain-lain payroll (per proyek + periode).
     *
     * Menyimpan daftar biaya tambahan (item JSON berisi nama & jumlah) yang
     * diinput saat generate payroll. Satu record mewakili satu proyek pada
     * satu periode mingguan.
     */
    public function up(): void
    {
        Schema::create('payroll_additional_costs', function (Blueprint $table) {
            $table->id();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('project_name')->nullable();
            $table->json('items')->nullable();
            $table->integer('total_amount')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index(['project_name', 'period_start_date', 'period_end_date'], 'pac_period_idx');
            $table->unique(['created_by', 'project_name', 'period_start_date', 'period_end_date'], 'pac_unique_project_period');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_additional_costs');
    }
};
