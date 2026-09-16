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
     * Menghapus data sekaligus membersihkan file bukti milik data yang dihapus
     * agar tidak menjadi file yatim di storage.
     *
     * @param  array<int, string> $ids  Daftar reimburse_code yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function bulkDelete(array $ids): int
    {
        $reimburses = Reimburse::whereIn('reimburse_code', $ids)->get();

        foreach ($reimburses as $reimburse) {
            $this->deleteProofFile($reimburse->proof_file);
        }

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
