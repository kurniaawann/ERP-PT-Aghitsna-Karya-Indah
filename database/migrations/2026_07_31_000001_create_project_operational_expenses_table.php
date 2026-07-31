<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Buat tabel pengeluaran tambahan / operasional proyek.
     *
     * Satu baris = satu periode payroll (seminggu) satu proyek.
     * Tidak diduplikasi per karyawan seperti additional_expenses lama.
     */
    public function up(): void
    {
        Schema::create('project_operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('project_name')->nullable();
            $table->json('expense_items')->nullable();
            $table->integer('total_amount')->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_start_date', 'period_end_date'], 'poe_period_idx');
        });
    }

    /**
     * Hapus tabel.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_operational_expenses');
    }
};
