<?php

namespace App\Services\Finance;

use App\Models\Finance\ProjectFinancialReport;
use App\Models\Finance\ProjectFinancialReportItem;
use App\Models\Finance\ProjectRecap;
use App\Models\Report\TransactionCategory;
use App\Services\InputNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service layer untuk operasi bisnis Laporan Keuangan Proyek.
 *
 * Menangani semua logika bisnis terkait laporan keuangan proyek:
 * - Pembuatan laporan otomatis (1 rekap proyek = 1 laporan, auto-create)
 * - Pembuatan ID unik (race-condition safe)
 * - CRUD item "Bon" termasuk upload bukti pembayaran
 * - Perhitungan grand totals (income, expense, balance)
 * - Bulk delete
 */
class ProjectFinancialReportService
{
    /**
     * Direktori penyimpanan bukti pembayaran pada disk public.
     */
    private const PROOF_DIRECTORY = 'project-financial-reports';

    /**
     * Membangun atau mengambil laporan keuangan proyek untuk sebuah rekap.
     *
     * Laporan dibuat otomatis saat tombol "Laporan Keuangan" pada tabel
     * Rekap Proyek diklik pertama kali (1 rekap = 1 laporan). Menggunakan
     * unique constraint project_recap_id untuk mencegah duplikat saat
     * bersamaan (race condition).
     */
    public function getOrCreateForRecap(ProjectRecap $recap): ProjectFinancialReport
    {
        $report = $recap->financialReport;

        if ($report) {
            return $report;
        }

        try {
            return ProjectFinancialReport::create([
                'project_recap_id' => $recap->id,
                'created_by' => auth()->id(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            // Duplicate entry: laporan sudah dibuat oleh request lain.
            $report = ProjectFinancialReport::where('project_recap_id', $recap->id)->first();

            if (! $report) {
                throw $e;
            }

            return $report;
        }
    }

    /**
     * Ambil semua item laporan yang urut berdasarkan kategori lalu tanggal.
     *
     * Urutan tampil mengikuti kategori (sort_order), kemudian tanggal naik,
     * kemudian id sebagai tie-breaker agar deterministik.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Finance\ProjectFinancialReportItem>
     */
    public function getItems(ProjectFinancialReport $report): Collection
    {
        return $report->items()
            ->with(['category'])
            ->orderByRaw('(SELECT sort_order FROM transaction_categories WHERE transaction_categories.id = project_financial_report_items.transaction_category_id)')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Hitung grand totals (total income, total expense, balance).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Finance\ProjectFinancialReportItem>  $items
     * @return object Object berisi total_income, total_expense, balance
     */
    public function getGrandTotals($items): object
    {
        $totalIncome = (int) $items->sum('income_amount');
        $totalExpense = (int) $items->sum('expense_amount');

        return (object) [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * Membuat item "Bon" baru pada laporan keuangan proyek.
     *
     * Logika INCOME vs EXPENSE (ditentukan dari tipe kategori transaksi):
     * - Kategori INCOME: expense_amount dipindah ke income_amount.
     * - Kategori selain INCOME (EXPENSE): income_amount null, expense_amount dipakai.
     *
     * @param  array<string, mixed>  $data  Data yang sudah validasi dari FormRequest
     * @param  \Illuminate\Http\UploadedFile|null  $proofFile  Bukti pembayaran (opsional)
     */
    public function createItem(ProjectFinancialReport $report, array $data, ?UploadedFile $proofFile): ProjectFinancialReportItem
    {
        $data['project_financial_report_id'] = $report->id;

        $data = $this->resolveAmounts($data);

        if ($proofFile) {
            $stored = $this->storeProofFile($proofFile);
            $data['proof_file'] = $stored['file_path'];
            $data['proof_file_name'] = $stored['file_name'];
        }

        $data['created_by'] = auth()->id();

        try {
            return ProjectFinancialReportItem::create($data);
        } catch (\Throwable $throwable) {
            if (isset($data['proof_file'])) {
                $this->deleteProofFile($data['proof_file']);
            }
            throw $throwable;
        }
    }

    /**
     * Mengupdate item "Bon" yang sudah ada.
     *
     * Bukti pembayaran hanya diganti jika ada file baru yang diunggah;
     * file lama dihapus setelah data berhasil disimpan.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(ProjectFinancialReportItem $item, array $data, ?UploadedFile $proofFile): bool
    {
        $oldFilePath = $item->proof_file;
        $storedFile = null;

        try {
            $data = $this->resolveAmounts($data);

            if ($proofFile) {
                $storedFile = $this->storeProofFile($proofFile);
                $data['proof_file'] = $storedFile['file_path'];
                $data['proof_file_name'] = $storedFile['file_name'];
            }

            $updated = $item->update($data);

            if ($storedFile && $oldFilePath && $oldFilePath !== $storedFile['file_path']) {
                $this->deleteProofFile($oldFilePath);
            }

            return $updated;
        } catch (\Throwable $throwable) {
            if (isset($storedFile['file_path'])) {
                $this->deleteProofFile($storedFile['file_path']);
            }
            throw $throwable;
        }
    }

    /**
     * Menentukan income_amount / expense_amount berdasarkan tipe kategori.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveAmounts(array $data): array
    {
        $category = TransactionCategory::find($data['transaction_category_id']);
        $amount = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);

        if ($category && $category->type === TransactionCategory::TYPE_INCOME) {
            $data['income_amount'] = $amount;
            $data['expense_amount'] = null;
        } else {
            $data['income_amount'] = null;
            $data['expense_amount'] = $amount;
        }

        return $data;
    }

    /**
     * Hapus beberapa item "Bon" sekaligus (bulk delete).
     *
     * File bukti pembayaran milik item yang dihapus ikut dibersihkan.
     *
     * @param  array<int>  $ids  Daftar ID item
     * @return int Jumlah item yang dihapus
     */
    public function bulkDeleteItems(array $ids): int
    {
        $deletedCount = 0;

        ProjectFinancialReportItem::whereIn('id', $ids)->each(function ($item) use (&$deletedCount) {
            $this->deleteProofFile($item->proof_file);
            $item->delete();
            $deletedCount++;
        });

        return $deletedCount;
    }

    /**
     * Generate unique laporan keuangan proyek ID (format: LFP-00001).
     *
     * Prefix: LFP (Laporan Financial Proyek)
     * Sequential number: 5 digit zero-padded.
     *
     * Menggunakan database lock untuk mencegah race condition.
     */
    public function generateId(): string
    {
        $lastReport = ProjectFinancialReport::lockForUpdate()
            ->where('id', 'like', 'LFP-%')
            ->orderByDesc('id')
            ->first();

        if ($lastReport && preg_match('/^LFP-(\d+)$/', $lastReport->id, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $newId = 'LFP-'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        while (ProjectFinancialReport::where('id', $newId)->exists()) {
            $nextNumber++;
            $newId = 'LFP-'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Ambil semua kategori transaksi aktif milik modul Laporan Keuangan Proyek.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Report\TransactionCategory>
     */
    public function getProjectFinanceCategories()
    {
        $userId = auth()->id();
        $cacheKey = 'finance:project-finance-categories:'.$userId;

        $query = fn () => TransactionCategory::where('created_by', $userId)
            ->module(TransactionCategory::MODULE_PROJECT_FINANCE)
            ->active()
            ->orderBy('sort_order')
            ->get();

        try {
            return Cache::remember($cacheKey, now()->addDay(), $query);
        } catch (\Exception $e) {
            Log::warning('Cache READ error ['.$cacheKey.']: '.$e->getMessage());

            return $query();
        }
    }

    /**
     * Invalidate cache kategori modul Laporan Keuangan Proyek.
     */
    public function flushCategoryCache(?string $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        try {
            Cache::forget('finance:project-finance-categories:'.$userId);
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [finance:project-finance-categories]: '.$e->getMessage());
        }
    }

    /**
     * Menyimpan file bukti pembayaran.
     *
     * File disimpan apa adanya dengan nama UUID di Storage::disk('public').
     * Path yang disimpan ke DB adalah path RELATIF agar portabel antar server.
     *
     * @return array{file_name: string, file_path: string}
     */
    private function storeProofFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = Str::uuid()->toString().'.'.$extension;
        $relativePath = self::PROOF_DIRECTORY.'/'.$fileName;

        $file->storeAs(self::PROOF_DIRECTORY, $fileName, ['disk' => 'public']);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
        ];
    }

    /**
     * Menghapus file bukti pembayaran berdasarkan path relatif.
     */
    private function deleteProofFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }
}
