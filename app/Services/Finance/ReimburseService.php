<?php

namespace App\Services\Finance;

use App\Models\Finance\Reimburse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * Direktori penyimpanan file bukti reimburse di Storage::disk('public').
     */
    private const PROOF_DIRECTORY = 'reimburses';

    /**
     * Membangun query dasar untuk listing reimburse.
     *
     * Menerapkan filter search, status, month, year pada query builder.
     * Digunakan untuk halaman index, export PDF, dan export Excel.
     *
     * @param  \Illuminate\Http\Request|null $request  Request yang berisi parameter filter
     * @param  string|null                   $orderBy  Kolom pengurutan (default 'date');
     *                                                 halaman web memakai 'created_at'
     *                                                 agar data terbaru tampil di atas,
     *                                                 export tetap memakai 'date'.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildFilteredQuery(?Request $request = null, ?string $orderBy = 'date'): Builder
    {
        $query = Reimburse::query();

        if (!$request) {
            return $query->latest($orderBy);
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
            ->latest($orderBy);
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
     * Jika ada file bukti diupload, file disimpan dan path-nya dicatat.
     *
     * @param  array<string, mixed> $validated   Data yang sudah validasi
     * @param  \Illuminate\Http\UploadedFile|null $proofFile  File bukti (opsional)
     * @return \App\Models\Finance\Reimburse
     */
    public function storeReimburse(array $validated, ?UploadedFile $proofFile = null): Reimburse
    {
        $validated['reimburse_code'] = $this->generateReimburseCode();
        $validated['status'] = 'draft';

        if ($proofFile) {
            $stored = $this->storeProofFile($proofFile);
            $validated['proof_file'] = $stored['file_path'];
            $validated['proof_file_name'] = $stored['file_name'];
        }

        return Reimburse::create($validated);
    }

    /**
     * Memperbarui data reimburse.
     *
     * Hanya data dengan status 'draft' yang dapat diperbarui.
     * Jika file bukti baru diupload, file lama dihapus dan diganti.
     *
     * @param  \App\Models\Finance\Reimburse $reimburse  Model yang akan diupdate
     * @param  array<string, mixed>           $validated  Data yang sudah validasi
     * @param  \Illuminate\Http\UploadedFile|null $proofFile  File bukti baru (opsional)
     * @return bool
     *
     * @throws \RuntimeException  Jika status bukan draft
     */
    public function updateReimburse(Reimburse $reimburse, array $validated, ?UploadedFile $proofFile = null): bool
    {
        if ($reimburse->status !== 'draft') {
            throw new \RuntimeException('Reimburse yang sudah disetujui/ditolak tidak dapat diubah!');
        }

        if ($proofFile) {
            $this->deleteProofFile($reimburse->proof_file);
            $stored = $this->storeProofFile($proofFile);
            $validated['proof_file'] = $stored['file_path'];
            $validated['proof_file_name'] = $stored['file_name'];
        }

        return $reimburse->update($validated);
    }

    /**
     * Bulk approve reimburse.
     *
     * Mengubah status menjadi 'approved' untuk semua reimburse draft yang dipilih.
     * Mengembalikan kode reimburse yang benar-benar disetujui (hanya yang
     * sebelumnya berstatus draft) — dipakai untuk langsung menampilkan
     * pratinjau dokumen reimburse yang disetujui.
     *
     * @param  array<int, string> $ids  Daftar reimburse_code yang akan di-approve
     * @return array<int, string>  Daftar reimburse_code yang disetujui
     */
    public function bulkApprove(array $ids): array
    {
        $codes = Reimburse::whereIn('reimburse_code', $ids)
            ->where('status', 'draft')
            ->pluck('reimburse_code')
            ->all();

        if (empty($codes)) {
            return [];
        }

        Reimburse::whereIn('reimburse_code', $codes)
            ->where('status', 'draft')
            ->update([
                'status' => 'approved',
                'status_changed_at' => now(),
            ]);

        return $codes;
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
     * Menghapus data sekaligus membersihkan file bukti milik data yang dihapus
     * agar tidak menjadi file yatim di storage.
     *
     * Checkbox kini tersedia di semua baris (untuk cetak data terpilih), sehingga
     * role admin dibatasi hanya menghapus pengajuan berstatus draft — sama seperti
     * perilaku sebelumnya ketika admin hanya bisa mencentang baris draft.
     *
     * @param  array<int, string> $ids        Daftar reimburse_code yang akan dihapus
     * @param  bool               $onlyDraft  true = hanya hapus yang berstatus draft
     * @return int  Jumlah record yang dihapus
     */
    public function bulkDelete(array $ids, bool $onlyDraft = false): int
    {
        $query = fn () => Reimburse::whereIn('reimburse_code', $ids)
            ->when($onlyDraft, fn ($builder) => $builder->where('status', 'draft'));

        foreach ($query()->get() as $reimburse) {
            $this->deleteProofFile($reimburse->proof_file);
        }

        return $query()->delete();
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
     * - Bila request membawa `ids[]` (Export Dipilih / pratinjau setelah
     *   disetujui): hanya reimburse dengan kode tersebut, filter lain diabaikan.
     * - Selain itu: semua data yang sesuai filter (search/status/bulan/tahun)
     *   tanpa pagination.
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter / ids[]
     * @return \Illuminate\Support\Collection
     */
    public function getExportData(Request $request): Collection
    {
        $ids = $this->selectedIds($request);

        if (!empty($ids)) {
            return Reimburse::whereIn('reimburse_code', $ids)->latest('date')->get();
        }

        return $this->buildFilteredQuery($request)->get();
    }

    /**
     * Ambil daftar kode reimburse terpilih (`ids[]`) dari request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array<int, string>
     */
    public function selectedIds(Request $request): array
    {
        return array_values(array_filter(array_map('strval', (array) $request->input('ids', []))));
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

    /**
     * Teks status untuk header PDF/Excel (revisi klien: menggantikan "SEMUA").
     *
     * Menggambarkan rasio persetujuan, mis. "Disetujui 3 dari 4 yang diajukan".
     * - Tanpa filter status / data terpilih: jumlah disetujui dari seluruh data export.
     * - Filter satu status: "DISETUJUI (3 dari 4 yang diajukan)" — pembandingnya
     *   seluruh pengajuan dengan filter lain yang sama (search/bulan/tahun).
     *
     * @param  \Illuminate\Http\Request        $request     Request export
     * @param  \Illuminate\Support\Collection  $reimburses  Data yang di-export
     * @return string
     */
    public function buildStatusText(Request $request, Collection $reimburses): string
    {
        $status = $request->input('status');
        $labels = ['draft' => 'DRAFT', 'approved' => 'DISETUJUI', 'rejected' => 'DITOLAK'];

        if (!empty($this->selectedIds($request)) || !isset($labels[$status])) {
            $approved = $reimburses->where('status', 'approved')->count();

            return "Disetujui {$approved} dari {$reimburses->count()} yang diajukan";
        }

        // Total pengajuan dengan filter yang sama, tanpa filter status
        $submitted = $this->buildFilteredQuery(new Request($request->except('status')))->count();

        return "{$labels[$status]} ({$reimburses->count()} dari {$submitted} yang diajukan)";
    }

    /**
     * Menyimpan file bukti reimburse.
     *
     * Jika GD tersedia: gambar di-resize maksimal 1200×1200 (proporsional, tidak
     * diperbesar) lalu dikonversi ke WEBP kualitas 80 dan disimpan dengan nama
     * UUID di Storage::disk('public'). Jika GD tidak tersedia / bukan gambar
     * valid: file disimpan apa adanya. Path yang disimpan ke DB adalah path
     * RELATIF agar portabel antar server.
     *
     * @return array{file_name: string, file_path: string}
     */
    private function storeProofFile(UploadedFile $file): array
    {
        $convertedPath = $this->convertImageToWebp($file);

        if ($convertedPath !== null) {
            return [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $convertedPath,
            ];
        }

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
     * Mengonversi file gambar menjadi WEBP (resize maksimal 1200×1200).
     *
     * Mengembalikan path relatif file WEBP yang tersimpan, atau null jika
     * konversi tidak memungkinkan (GD tidak tersedia, file bukan gambar valid,
     * atau webp tidak didukung).
     */
    private function convertImageToWebp(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            return null;
        }

        $imageInfo = @getimagesize($file->getPathname());

        if ($imageInfo === false) {
            return null;
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;
        $sourceImage = match ($file->getMimeType()) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($file->getPathname()),
            'image/png' => imagecreatefrompng($file->getPathname()),
            'image/gif' => imagecreatefromgif($file->getPathname()),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($file->getPathname()) : null,
            default => null,
        };

        if (! $sourceImage) {
            return null;
        }

        $maxWidth = 1200;
        $maxHeight = 1200;
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = (int) round($sourceWidth * $ratio);
        $targetHeight = (int) round($sourceHeight * $ratio);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $fileName = Str::uuid()->toString().'.webp';
        $relativePath = self::PROOF_DIRECTORY.'/'.$fileName;

        $tempPath = tempnam(sys_get_temp_dir(), 'proof_');

        if (! imagewebp($canvas, $tempPath, 80)) {
            imagedestroy($sourceImage);
            imagedestroy($canvas);
            @unlink($tempPath);

            return null;
        }

        imagedestroy($sourceImage);
        imagedestroy($canvas);

        Storage::disk('public')->put($relativePath, file_get_contents($tempPath));
        @unlink($tempPath);

        return $relativePath;
    }

    /**
     * Menghapus file bukti berdasarkan path relatif.
     */
    private function deleteProofFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }
}
