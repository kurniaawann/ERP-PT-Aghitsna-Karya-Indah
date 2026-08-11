<?php

namespace App\Services\Finance;

use App\Models\Finance\ProjectRecap;
use App\Services\InputNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
     */
    public function buildIndexQuery(Request $request): Builder
    {
        return ProjectRecap::query()
            ->where('created_by', auth()->id())
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->search;
                $query->where('project_name', 'like', "%{$search}%");
            })
            ->with(['creator', 'rab', 'paymentProofs'])
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
     * @param  array<string, mixed>  $data  Data yang sudah validasi dari FormRequest
     * @param  \Illuminate\Http\UploadedFile|null  $designFile  File design yang diunggah
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
     * Jika nama proyek berubah:
     * - Referensi yang menyimpan nama proyek sebagai string (payroll, karyawan,
     *   kasbon) ikut disinkronkan via renameProject() agar tidak putus.
     * - Bila rekap dibuat otomatis dari RAB (rab_number terisi), nama proyek
     *   pada RAB ikut diubah agar dokumen induk tetap konsisten.
     *
     * Total RAB pada rekap yang dibuat otomatis dari RAB tidak bisa diubah di
     * sini — selalu mengikuti total_amount RAB sumber. Perubahan hanya lewat
     * modul RAB.
     *
     * @param  \App\Models\Finance\ProjectRecap  $recap  Model yang akan diupdate
     * @param  array<string, mixed>  $data  Data yang sudah validasi dari FormRequest
     * @param  \Illuminate\Http\UploadedFile|null  $designFile  File design baru (opsional)
     */
    public function updateRecap(ProjectRecap $recap, array $data, ?UploadedFile $designFile): bool
    {
        $oldFilePath = $recap->design_file;
        $oldProjectName = $recap->project_name;
        $storedFile = null;

        try {
            $data['total_rab'] = InputNormalizer::normalizeCurrency($data['total_rab'] ?? null);

            // Rekap yang dibuat otomatis dari RAB (rab_number terisi): Total RAB
            // tidak bisa diubah di sini — nilainya selalu mengikuti RAB sumber.
            // Perubahan Total RAB hanya lewat modul RAB (RABService::updateRAB).
            if ($recap->rab_number && ($rabTotal = $recap->rab?->total_amount) !== null) {
                $data['total_rab'] = $rabTotal;
            }

            if ($designFile) {
                $storedFile = $this->storeDesignFile($designFile);
                $data['design_file'] = $storedFile['file_path'];
                $data['design_file_name'] = $storedFile['file_name'];
            }

            $updated = $recap->update($data);

            // Nama proyek berubah → sinkronkan referensi (payroll, karyawan,
            // kasbon) agar tidak putus.
            $newProjectName = $data['project_name'] ?? $oldProjectName;
            $this->renameProject($oldProjectName, $newProjectName, $recap->created_by);

            // Rekap yang dibuat otomatis dari RAB (rab_number terisi): nama
            // proyek pada RAB ikut disinkronkan agar dokumen induk tetap
            // konsisten dengan rekap (sama seperti update RAB yang menyinkronkan
            // nama ke rekap).
            if ($recap->rab_number && $oldProjectName !== $newProjectName) {
                DB::table('rabs')
                    ->where('rab_number', $recap->rab_number)
                    ->where('created_by', $recap->created_by)
                    ->update(['project_name' => $newProjectName]);
            }

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
     * @return int Jumlah rekap yang dihapus
     */
    public function bulkDelete(array $ids): int
    {
        $deletedCount = 0;

        ProjectRecap::where('created_by', auth()->id())
            ->whereIn('id', $ids)
            ->each(function ($recap) use (&$deletedCount) {
                // Laporan Keuangan Proyek (1:1) ikut dihapus agar tidak
                // meninggalkan record yatim. Guard di findUsedRecapIds menjamin
                // laporan yang masih punya transaksi tidak akan sampai ke sini.
                $report = $recap->financialReport;

                if ($report) {
                    $report->items()->each(function ($item) {
                        $this->deleteProofFile($item->proof_file);
                    });
                    $report->items()->delete();
                    $report->delete();
                }

                $recap->delete();
                $this->deleteDesignFile($recap->design_file);
                $deletedCount++;
            });

        return $deletedCount;
    }

    /**
     * Mendeteksi rekap proyek yang masih dipakai data lain.
     *
     * Rekap proyek tidak boleh dihapus bila masih direferensikan agar tidak
     * meninggalkan data yatim (sinkronisasi data). Referensi yang dicek (semua
     * dibatasi ke user yang sama dengan pemilik rekap):
     * - Laporan Keuangan Proyek yang masih punya baris transaksi
     *   (project_financial_reports + project_financial_report_items)
     * - Payroll (payrolls.project_name)
     * - Kasbon (kasbons.project_names — JSON berisi nama proyek)
     * - Karyawan (employees.project_name)
     *
     * @param  array<int, string>  $ids  Daftar ID rekap yang akan dicek
     * @return array<int, string> ID rekap yang masih digunakan
     */
    public function findUsedRecapIds(array $ids): array
    {
        $recaps = ProjectRecap::where('created_by', auth()->id())
            ->whereIn('id', $ids)
            ->get(['id', 'project_name', 'created_by']);

        if ($recaps->isEmpty()) {
            return [];
        }

        $usedIds = [];

        // Referensi via Laporan Keuangan Proyek: hanya dianggap "dipakai" bila
        // laporan masih memiliki baris transaksi (data nyata). Laporan kosong
        // (tanpa item) adalah scaffolding yang boleh ikut terhapus saat rekap
        // dihapus — jadi tidak memblokir penghapusan.
        $reportRecapIds = DB::table('project_financial_reports as r')
            ->whereIn('r.project_recap_id', $recaps->pluck('id'))
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('project_financial_report_items as i')
                    ->whereColumn('i.project_financial_report_id', 'r.id');
            })
            ->pluck('r.project_recap_id')
            ->all();
        $usedIds = array_merge($usedIds, $reportRecapIds);

        foreach ($recaps as $recap) {
            $projectName = trim($recap->project_name ?? '');

            if ($projectName === '') {
                continue;
            }

            $referencedBySdm = DB::table('payrolls')
                ->where('created_by', $recap->created_by)
                ->where('project_name', $projectName)
                ->exists()
                || DB::table('employees')
                    ->where('created_by', $recap->created_by)
                    ->where('project_name', $projectName)
                    ->exists()
                || DB::table('kasbons')
                    ->where('created_by', $recap->created_by)
                    ->whereJsonContains('project_names', $projectName)
                    ->exists();

            if ($referencedBySdm) {
                $usedIds[] = $recap->id;
            }
        }

        return array_values(array_unique($usedIds));
    }

    /**
     * Mendapatkan label nama proyek untuk pesan error.
     *
     * @param  array<int, string>  $ids  Daftar ID rekap proyek
     * @return string String "Proyek A, Proyek B"
     */
    public function getRecapLabels(array $ids): string
    {
        return ProjectRecap::whereIn('id', $ids)
            ->get()
            ->map(fn ($recap) => $recap->project_name ?: $recap->id)
            ->implode(', ');
    }

    /**
     * Mengganti nama proyek pada data yang mereferensikannya via string.
     *
     * Referensi proyek disimpan sebagai string (payrolls.project_name,
     * employees.project_name, dan kasbons.project_names — JSON array) sehingga
     * saat nama proyek diubah, semua referensi milik pemilik data harus ikut
     * diubah agar tidak putus. Tanpa sinkronisasi ini, guard findUsedRecapIds
     * dan pencocokan rekap berdasarkan nama proyek akan gagal menemukan data.
     *
     * Hanya menyentuh payroll, karyawan, dan kasbon; rekap itu sendiri diubah
     * oleh pemanggil.
     *
     * @param  string  $oldName  Nama proyek lama
     * @param  string  $newName  Nama proyek baru
     * @param  int|string|null  $userId  Pemilik data; default: user login
     */
    public function renameProject(string $oldName, string $newName, $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        $oldName = trim($oldName);
        $newName = trim($newName);

        if (! $userId || $oldName === '' || $newName === '' || $oldName === $newName) {
            return;
        }

        DB::table('payrolls')
            ->where('created_by', $userId)
            ->where('project_name', $oldName)
            ->update(['project_name' => $newName]);

        DB::table('employees')
            ->where('created_by', $userId)
            ->where('project_name', $oldName)
            ->update(['project_name' => $newName]);

        DB::table('kasbons')
            ->where('created_by', $userId)
            ->whereJsonContains('project_names', $oldName)
            ->get(['kasbon_code', 'project_names'])
            ->each(function ($kasbon) use ($oldName, $newName) {
                $names = json_decode($kasbon->project_names, true);

                if (! is_array($names)) {
                    return;
                }

                $renamed = array_map(
                    fn ($name) => $name === $oldName ? $newName : $name,
                    $names
                );

                DB::table('kasbons')
                    ->where('kasbon_code', $kasbon->kasbon_code)
                    ->update(['project_names' => json_encode(array_values(array_unique($renamed)))]);
            });
    }

    /**
     * Generate unique rekap proyek ID (format: RP-00001).
     *
     * Prefix: RP (Recap Proyek)
     * Sequential number: 5 digit zero-padded
     * Contoh: RP-00001, RP-00002, dst.
     *
     * Menggunakan database lock untuk mencegah race condition.
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

        $newId = 'RP-'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        while (ProjectRecap::where('id', $newId)->exists()) {
            $nextNumber++;
            $newId = 'RP-'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Menyimpan file design rekap proyek.
     *
     * File disimpan apa adanya dengan nama UUID di Storage::disk('public').
     * Path yang disimpan ke DB adalah path RELATIF agar portabel antar server.
     *
     * @return array{file_name: string, file_path: string}
     */
    private function storeDesignFile(UploadedFile $file): array
    {
        $relativeDirectory = 'recap-proyek/designs';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = Str::uuid()->toString().'.'.$extension;
        $relativePath = $relativeDirectory.'/'.$fileName;

        $file->storeAs($relativeDirectory, $fileName, ['disk' => 'public']);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
        ];
    }

    /**
     * Menghapus file design berdasarkan path relatif.
     */
    private function deleteDesignFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }

    /**
     * Menghapus file bukti milik item laporan yang ikut terhapus.
     *
     * File bukti pada item laporan keuangan hanya dikelola oleh modul Bukti
     * Pembayaran, tapi ketika item laporan dihapus ikut rekap proyek, filenya
     * dibersihkan agar tidak menjadi file yatim.
     */
    private function deleteProofFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }
}
