<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // expense_recaps — 2 index
        // ═══════════════════════════════════════════════════════════════
        // idx_expense_recaps_created_by
        // Digunakan oleh: ExpenseReportService → WHERE created_by = auth()->id()
        //   di SEMUA query (buildFilteredQuery, buildIndexQuery, calculateSummary,
        //   getMonthlyTrend, getCashFlow, buildExportData).
        //   RecapExpenseService → WHERE created_by = auth()->id()
        // Alasan: created_by adalah base filter di SETIAP request. Tanpa index,
        //   database harus scan seluruh tabel expense_recaps (yang bisa ribuan baris)
        //   hanya untuk memfilter data user yang login. Ini bottleneck terbesar
        //   di modul Report.
        //
        // idx_expense_recaps_created_by_date
        // Digunakan oleh: ExpenseReportService → WHERE created_by + whereMonth/Year('transaction_date')
        //   RecapExpenseService → WHERE created_by + whereMonth/Year('transaction_date')
        // Alasan: Composite index (created_by, transaction_date) menutupi pola query
        //   paling umum: filter per user + filter bulan/tahun. Database bisa langsung
        //   jumpa ke partition data user, lalu filter date tanpa scan ulang.
        //   Lebih efisien dari 2 index terpisah karena mengurangi I/O disk.
        Schema::table('expense_recaps', function (Blueprint $table) {
            $table->index('created_by', 'idx_expense_recaps_created_by');
            $table->index(['created_by', 'transaction_date'], 'idx_expense_recaps_created_by_date');
        });
    }

    public function down(): void
    {
        Schema::table('expense_recaps', function (Blueprint $table) {
            $table->dropIndex('idx_expense_recaps_created_by');
            $table->dropIndex('idx_expense_recaps_created_by_date');
        });
    }
};
