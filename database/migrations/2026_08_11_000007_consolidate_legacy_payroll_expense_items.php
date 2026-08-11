<?php

use App\Models\Report\ProjectFinancialReportItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Konsolidasi entri "Upah" lama pada Laporan Keuangan Proyek.
     *
     * Versi lama membuat satu baris PER KARYAWAN ("Upah {nama} periode ...").
     * Versi baru membuat satu baris AGREGAT per proyek + periode
     * ("Upah Kerja periode ..."). Migrasi ini merapikan data yang sudah ada:
     * - Mengelompokkan entri lama per (laporan, periode).
     * - Memastikan satu baris agregat "Upah Kerja periode ..." ada dengan
     *   total yang benar (dibuat bila belum ada, diperbarui bila sudah ada).
     * - Menghapus entri lama per karyawan.
     */
    public function up(): void
    {
        $oldItems = ProjectFinancialReportItem::where('description', 'LIKE', 'Upah %periode%')
            ->where('description', 'NOT LIKE', 'Upah Kerja%')
            ->get();

        if ($oldItems->isEmpty()) {
            return;
        }

        $parsePeriod = static function (string $description): ?string {
            if (preg_match('/periode (\d{2}\/\d{2}\/\d{4})(?: - (\d{2}\/\d{2}\/\d{4}))?$/', $description, $m)) {
                return $m[1].($m[2] ?? null ? ' - '.$m[2] : '');
            }

            return null;
        };

        // Hanya entri dengan pola "Upah <nama> periode dd/mm/yyyy[- dd/mm/yyyy]".
        $oldItems = $oldItems->filter(fn ($item) => $parsePeriod($item->description) !== null);

        $groups = $oldItems->groupBy(fn ($item) => $item->project_financial_report_id.'|'.$parsePeriod($item->description));

        foreach ($groups as $key => $group) {
            [$reportId, $periodRange] = explode('|', $key, 2);
            $newDescription = 'Upah Kerja periode '.$periodRange;

            $total = (int) $group->sum('expense_amount');
            $first = $group->first();
            $transactionDate = $group->min('transaction_date') ?? $first->transaction_date;

            $existing = ProjectFinancialReportItem::where('project_financial_report_id', $reportId)
                ->where('description', $newDescription)
                ->first();

            if ($existing) {
                $existing->update(['expense_amount' => $total]);
            } else {
                ProjectFinancialReportItem::create([
                    'project_financial_report_id' => $reportId,
                    'transaction_category_id' => $first->transaction_category_id,
                    'transaction_date' => $transactionDate,
                    'description' => $newDescription,
                    'income_amount' => null,
                    'expense_amount' => $total,
                    'created_by' => $first->created_by,
                ]);
            }
        }

        ProjectFinancialReportItem::whereIn('id', $oldItems->pluck('id'))->delete();
    }

    /**
     * Reverse the migrations.
     *
     * Konsolidasi data tidak bisa dibatalkan secara aman; baris lama sudah
     * terhapus. Migrasi sengaja dibuat one-way.
     */
    public function down(): void
    {
        //
    }
};
