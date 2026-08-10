<?php

namespace App\Services\Finance;

use App\Models\Finance\ProjectRecap;
use App\Services\InputNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Service untuk Rekap Proyek (standalone).
 *
 * Modul mandiri yang menyimpan rekap proyek secara manual:
 * - No (ID auto-generate format RP-00001)
 * - Nama Proyek
 * - Total RAB
 * - File design (unggahan)
 *
 * Business logic didelegasikan dari RecapProyekController ke service ini.
 */
class RecapProyekService
{
    /**
     * Membangun query dasar untuk listing rekap proyek.
     *
     * Data yang terlihat hanya milik user login (created_by = user).
     * Pencarian dilakukan pada kolom project_name.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildIndexQuery(Request $request): Builder
    {
        return ProjectRecap::query()
            ->where('created_by', auth()->id())
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->search;
                $query->where('project_name', 'like', "%{$search}%");
            })
            ->with('creator')
            ->orderByDesc('created_at');
    }

    /**
     * Membuat rekap proyek baru dari input manual user.
     *
     * Alur:
     * 1. Simpan file design ke public disk (jika ada).
     * 2. Buat record di database.
     * 3. Jika penyimpanan record gagal, file yang baru tersimpan ikut dihapus.
     *
     * @param  array<string, mixed>  $data        Data yang sudah validasi dari FormRequest
     * @param  \Illuminate\Http\UploadedFile|null  $designFile  File design yang diunggah
     * @return \App\Models\Finance\ProjectRecap
     */
    public function createRecap(array $data, ?UploadedFile $designFile): ProjectRecap
    {
        $storedFile = null;

        try {
            $data['total_rab'] = InputNormalizer::normalizeCurrency($data['total_rab'] ?? null);

            if ($designFile) {
                $storedFile = $this->storeDesignFile($designFile);
                $data['design_file'] = $storedFile['file_path'];
                $data['design_file_name'] = $storedFile['file_name'];
            }

            $data['created_by'] = auth()->id();

            $recap = ProjectRecap::create($data);
        } catch (Throwable $throwable) {
            Log::error('Recap Proyek store failed', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            if (isset($storedFile['file_path'])) {
                $this->deleteDesignFile($storedFile['file_path']);
            }

            throw $throwable;
        }

        return $recap;
    }

    /**
     * Mengupdate rekap proyek yang sudah ada.
     *
     * File design hanya diganti jika ada file baru yang diunggah;
     * file lama dihapus setelah data berhasil disimpan.
     *
     * @param  \App\Models\Finance\ProjectRecap  $recap       Model yang akan diupdate
     * @param  array<string, mixed>              $data        Data yang sudah validasi dari FormRequest
     * @param  \Illuminate\Http\UploadedFile|null $designFile  File design baru (opsional)
     * @return bool
     */
    public function updateRecap(ProjectRecap $recap, array $data, ?UploadedFile $designFile): bool
    {
        $oldFilePath = $recap->design_file;
        $storedFile = null;

        try {
            $data['total_rab'] = InputNormalizer::normalizeCurrency($data['total_rab'] ?? null);

            if ($designFile) {
                $storedFile = $this->storeDesignFile($designFile);
                $data['design_file'] = $storedFile['file_path'];
                $data['design_file_name'] = $storedFile['file_name'];
            }

            $updated = $recap->update($data);

            if (isset($storedFile['file_path']) && $oldFilePath && $oldFilePath !== $storedFile['file_path']) {
                $this->deleteDesignFile($oldFilePath);
            }
        } catch (Throwable $throwable) {
            Log::error('Recap Proyek update failed', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            if (isset($storedFile['file_path'])) {
                $this->deleteDesignFile($storedFile['file_path']);
            }

            throw $throwable;
        }

        return $updated;
    }

    /**
     * Hapus beberapa rekap proyek sekaligus (bulk delete).
     *
     * File design milik record yang dihapus ikut dibersihkan.
     *
     * @param  array<int, string>  $ids  Daftar ID rekap proyek
     * @return int  Jumlah rekap yang dihapus
     */
    public function bulkDelete(array $ids): int
    {
        $deletedCount = 0;

        ProjectRecap::whereIn('id', $ids)->each(function ($recap) use (&$deletedCount) {
            $recap->delete();
            $this->deleteDesignFile($recap->design_file);
            $deletedCount++;
        });

        return $deletedCount;
    }

    /**
     * Generate unique rekap proyek ID (format: RP-00001).
     *
     * Prefix: RP (Recap Proyek)
     * Sequential number: 5 digit zero-padded
     * Contoh: RP-00001, RP-00002, dst.
     *
     * Menggunakan database lock untuk mencegah race condition.
     *
     * @return string
     */
    public function generateId(): string
    {
        $lastRecap = ProjectRecap::lockForUpdate()
            ->where('id', 'like', 'RP-%')
            ->orderByDesc('id')
            ->first();

        if ($lastRecap && preg_match('/^RP-(\d+)$/', $lastRecap->id, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $newId = 'RP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        while (ProjectRecap::where('id', $newId)->exists()) {
            $nextNumber++;
            $newId = 'RP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Menyimpan file design rekap proyek.
     *
     * File disimpan apa adanya dengan nama UUID di Storage::disk('public').
     * Path yang disimpan ke DB adalah path RELATIF agar portabel antar server.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array{file_name: string, file_path: string}
     */
    private function storeDesignFile(UploadedFile $file): array
    {
        $relativeDirectory = 'recap-proyek/designs';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $relativePath = $relativeDirectory . '/' . $fileName;

        $file->storeAs($relativeDirectory, $fileName, ['disk' => 'public']);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
        ];
    }

    /**
     * Menghapus file design berdasarkan path relatif.
     *
     * @param  string|null  $relativePath
     * @return void
     */
    private function deleteDesignFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }
}
