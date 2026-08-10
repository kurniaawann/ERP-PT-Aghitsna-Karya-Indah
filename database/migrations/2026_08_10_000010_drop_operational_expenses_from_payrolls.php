<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Hapus seluruh fitur pengeluaran tambahan / operasional proyek.
     *
     * 1. Drop tabel project_operational_expenses.
     * 2. Drop kolom additional_expenses & additional_expenses_notes pada payrolls.
     */
    public function up(): void
    {
        Schema::dropIfExists('project_operational_expenses');

        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'additional_expenses')) {
                $table->dropColumn('additional_expenses');
            }
            if (Schema::hasColumn('payrolls', 'additional_expenses_notes')) {
                $table->dropColumn('additional_expenses_notes');
            }
        });
    }

    /**
     * Kembalikan struktur lama (tidak mengembalikan data yang telah dihapus).
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'additional_expenses')) {
                $table->integer('additional_expenses')->nullable();
            }
            if (!Schema::hasColumn('payrolls', 'additional_expenses_notes')) {
                $table->text('additional_expenses_notes')->nullable();
            }
        });

        Schema::create('project_operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('project_name')->nullable();
            $table->json('expense_items')->nullable();
            $table->integer('total_amount')->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index(['period_start_date', 'period_end_date'], 'poe_period_idx');
        });
    }
};
