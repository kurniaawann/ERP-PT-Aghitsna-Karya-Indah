<?php

namespace App\Services\Finance;

use App\Models\Finance\Reimburse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Service layer untuk operasi bisnis Reimbursement.
 *
 * Menangani semua logika bisnis terkait Reimbursement termasuk:
 * - Pencarian dan filter data
 * - Generasi kode reimburse
 * - Operasi CRUD
 * - Persetujuan dan penolakan
 * - Ekspor data
 */
class ReimburseService
{
    /**
     * Membangun query dasar untuk listing reimburse.
     *
     * Menerapkan filter search, status, month, year pada query builder.
     * Digunakan untuk halaman index, export PDF, dan export Excel.
     *
     * @param  \Illuminate\Http\Request|null $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildFilteredQuery(?Request $request = null): Builder
    {
        $query = Reimburse::query()->where('created_by', auth()->id());

        if (!$request) {
            return $query->latest('date');
        }

        $search = $request->input('search');
        $status = $request->input('status');
        $month = $request->input('month');
        $year = $request->input('year');

        return $query
            ->when($search, function ($builder) use ($search) {
                $builder->where(function ($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                        ->orWhere('reimburse_code', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($builder) => $builder->where('status', $status))
            ->when($month, fn ($builder) => $builder->whereMonth('date', $month))
            ->when($year, fn ($builder) => $builder->whereYear('date', $year))
            ->latest('date');
    }

    /**
     * Generate kode reimburse berikutnya.
     *
     * Format: RMB001, RMB002, RMB003, dst.
     * Mengambil kode terakhir dari database dan increment.
     *
     * @return string  Kode reimburse berikutnya
     */
    public function generateReimburseCode(): string
    {
        return Reimburse::generateReimburseCode();
    }

    /**
     * Menyimpan data reimburse baru.
     *
     * Auto-generate kode reimburse dan set status default 'draft'.
     *
     * @param  array<string, mixed> $validated  Data yang sudah validasi
     * @return \App\Models\Finance\Reimburse
     */
    public function storeReimburse(array $validated): Reimburse
    {
        $validated['reimburse_code'] = $this->generateReimburseCode();
        $validated['status'] = 'draft';
        $validated['created_by'] = auth()->id();

        return Reimburse::create($validated);
    }

    /**
     * Memperbarui data reimburse.
     *
     * Hanya data dengan status 'draft' yang dapat diperbarui.
     *
     * @param  \App\Models\Finance\Reimburse $reimburse  Model yang akan diupdate
     * @param  array<string, mixed>           $validated  Data yang sudah validasi
     * @return bool
     *
     * @throws \RuntimeException  Jika status bukan draft
     */
    public function updateReimburse(Reimburse $reimburse, array $validated): bool
    {
        if ($reimburse->status !== 'draft') {
            throw new \RuntimeException('Reimburse yang sudah disetujui/ditolak tidak dapat diubah!');
        }

        return $reimburse->update($validated);
    }

    /**
     * Bulk approve reimburse.
     *
     * Mengubah status menjadi 'approved' untuk semua reimburse draft yang dipilih.
     *
     * @param  array<int, string> $ids  Daftar reimburse_code yang akan di-approve
     * @return int  Jumlah record yang diupdate
     */
    public function bulkApprove(array $ids): int
    {
        return Reimburse::whereIn('reimburse_code', $ids)
            ->where('status', 'draft')
            ->update([
                'status' => 'approved',
                'status_changed_at' => now(),
            ]);
    }

    /**
     * Bulk reject reimburse.
     *
     * Mengubah status menjadi 'rejected' untuk semua reimburse draft yang dipilih.
     *
     * @param  array<int, string> $ids  Daftar reimburse_code yang akan ditolak
     * @return int  Jumlah record yang diupdate
     */
    public function bulkReject(array $ids): int
    {
        return Reimburse::whereIn('reimburse_code', $ids)
            ->where('status', 'draft')
            ->update([
                'status' => 'rejected',
                'status_changed_at' => now(),
            ]);
    }

    /**
     * Bulk delete reimburse.
     *
     * @param  array<int, string> $ids  Daftar reimburse_code yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function bulkDelete(array $ids): int
    {
        return Reimburse::whereIn('reimburse_code', $ids)->delete();
    }

    /**
     * Menghitung total amount dari reimburse yang dipilih.
     *
     * @param  array<int, string> $ids  Daftar reimburse_code
     * @return array{total: int, formatted_total: string}
     */
    public function getSelectedTotal(array $ids): array
    {
        $total = Reimburse::whereIn('reimburse_code', $ids)->sum('total_amount');

        return [
            'total' => $total,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.'),
        ];
    }

    /**
     * Mengambil data reimburse untuk export (PDF/Excel).
     *
     * Mengembalikan Collection semua data yang sesuai filter tanpa pagination.
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return \Illuminate\Support\Collection
     */
    public function getExportData(Request $request): Collection
    {
        return $this->buildFilteredQuery($request)->get();
    }

    /**
     * Menghitung ringkasan status dari collection reimburse.
     *
     * Digunakan untuk export PDF agar tidak perlu query ulang.
     *
     * @param  \Illuminate\Support\Collection $reimburses  Data reimburse
     * @return array{draft_count: int, approved_count: int, rejected_count: int, total_amount: int}
     */
    public function getStatusSummary(Collection $reimburses): array
    {
        return [
            'draft_count' => $reimburses->where('status', 'draft')->count(),
            'approved_count' => $reimburses->where('status', 'approved')->count(),
            'rejected_count' => $reimburses->where('status', 'rejected')->count(),
            'total_amount' => $reimburses->sum('total_amount'),
        ];
    }
}
