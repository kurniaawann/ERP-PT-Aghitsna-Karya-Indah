<?php

namespace App\Services\Finance;

use App\Models\Administrasi\Nota;
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
 * - Pengajuan otomatis dari Nota Super Admin (buat, sinkron, hapus draft)
 */
class ReimburseService
{
    /**
     * Direktori penyimpanan file bukti reimburse di Storage::disk('public').
     */
    private const PROOF_DIRECTORY = 'reimburses';

    /**
     * Label biaya tambahan nota sewa/jual untuk ringkasan keterangan belanja
     * reimburse otomatis (urutan sama dengan rincian biaya pada PDF nota).
     */
    private const NOTA_FEE_LABELS = [
        'sewa_jual' => 'Sewa/Jual',
        'ongkos_kirim' => 'Ongkos Kirim',
        'bongkar_pasang' => 'Bongkar/Pasang',
        'lembur' => 'Lembur Antar/Ambil',
        'uang_jaminan' => 'Uang Jaminan',
    ];

    /**
     * Jumlah maksimal jenis barang yang dirinci pada keterangan belanja
     * reimburse otomatis; sisanya diringkas menjadi "dan N item lainnya".
     */
    private const NOTA_DESCRIPTION_MAX_ITEMS = 10;

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
                        ->orWhere('reimburse_code', 'like', "%{$search}%")
                        ->orWhere('id_nota', 'like', "%{$search}%");
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
     * Membuat pengajuan reimburse otomatis dari Nota (revisi klien).
     *
     * Hanya untuk nota proyek yang dibuat OTOMATIS dari Invoice Semen oleh
     * Super Admin. Nota yang dibuat sendiri lewat "Tambah Nota" tidak otomatis
     * masuk reimbursement (revisi klien) — bisa dipilih manual lewat "Ambil
     * dari Nota" pada modal Tambah Reimburse. Nota buatan admin diabaikan.
     * Pengajuan berstatus draft, tanpa tanggal jatuh tempo, dan tertaut ke
     * nota lewat kolom id_nota.
     *
     * Idempoten: bila nota sudah punya reimburse, data yang ada dikembalikan
     * (unique index id_nota juga mencegah duplikat di level database).
     *
     * @param  \App\Models\Administrasi\Nota  $nota  Nota sumber
     * @return \App\Models\Finance\Reimburse|null  null bila bukan nota Invoice Semen buatan Super Admin
     */
    public function createFromNota(Nota $nota): ?Reimburse
    {
        if (!$nota->isFromSemenInvoice() || !$nota->creator?->isSuperAdmin()) {
            return null;
        }

        $existing = Reimburse::where('id_nota', $nota->id_nota)->first();

        if ($existing) {
            return $existing;
        }

        return Reimburse::create(array_merge($this->buildNotaPayload($nota), [
            'reimburse_code' => $this->generateReimburseCode(),
            'status' => 'draft',
            'notes' => $this->buildNotaNotes($nota),
            'id_nota' => $nota->id_nota,
        ]));
    }

    /**
     * Pilihan "Ambil dari Nota" pada modal Tambah Reimburse (Super Admin).
     *
     * Nota milik user login yang BELUM punya pengajuan reimburse — terutama
     * nota dari "Tambah Nota" (tidak otomatis masuk reimbursement), plus nota
     * Invoice Semen yang draft otomatisnya dihapus.
     * Setiap opsi membawa isian siap pakai untuk field form (tanggal, nama
     * proyek, keterangan belanja, total, catatan) — diisi otomatis oleh JS.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getNotaOptionsForReimburse(): array
    {
        return Nota::where('created_by', auth()->id())
            ->whereDoesntHave('reimburse')
            ->latest('nota_date')
            ->latest('created_at')
            ->get()
            ->map(function (Nota $nota) {
                $payload = $this->buildNotaPayload($nota);

                return array_merge($payload, [
                    'value' => $nota->id_nota,
                    'label' => $nota->id_nota.' — '.$payload['project_name']
                        .' — Rp '.number_format($payload['total_amount'], 0, ',', '.')
                        .($nota->nota_date ? ' ('.$nota->nota_date->format('d/m/Y').')' : ''),
                    'notes' => $this->buildNotaNotes($nota, false),
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * Menyinkronkan reimburse tertaut setelah nota diubah.
     *
     * Hanya pengajuan yang masih draft yang diperbarui (tanggal, nama proyek,
     * keterangan belanja, total). Pengajuan yang sudah disetujui/ditolak
     * tidak pernah diubah. Catatan & lampiran bukti tidak disentuh.
     * Nota tanpa reimburse tertaut (mis. nota lama, atau draft-nya sudah
     * dihapus manual) tidak dibuatkan pengajuan baru.
     *
     * @param  \App\Models\Administrasi\Nota  $nota  Nota yang baru diubah
     * @return \App\Models\Finance\Reimburse|null  Reimburse tertaut (bila ada)
     */
    public function syncFromNota(Nota $nota): ?Reimburse
    {
        $reimburse = Reimburse::where('id_nota', $nota->id_nota)->first();

        if (!$reimburse || $reimburse->status !== 'draft') {
            return $reimburse;
        }

        $reimburse->fill($this->buildNotaPayload($nota))->save();

        return $reimburse;
    }

    /**
     * Menghapus pengajuan reimburse draft milik nota yang akan dihapus.
     *
     * Dipanggil oleh NotaBuilder::delete() SEBELUM nota dihapus (setelah nota
     * terhapus, FK sudah mengosongkan id_nota). Pengajuan yang sudah
     * disetujui/ditolak tetap disimpan; tautannya dikosongkan oleh FK
     * ON DELETE SET NULL.
     *
     * @param  array<int, string>  $notaIds  Daftar id_nota
     * @return int  Jumlah reimburse draft yang dihapus
     */
    public function deleteDraftsForNotas(array $notaIds): int
    {
        $notaIds = array_values(array_filter(array_map('strval', $notaIds)));

        if (empty($notaIds)) {
            return 0;
        }

        $codes = Reimburse::whereIn('id_nota', $notaIds)
            ->where('status', 'draft')
            ->pluck('reimburse_code')
            ->all();

        return empty($codes) ? 0 : $this->bulkDelete($codes, true);
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
     * Ringkasan seluruh pengajuan sesuai filter halaman (search/bulan/tahun),
     * TANPA filter status dan tanpa melihat data terpilih — pembanding teks
     * "x dari y yang diajukan" di PDF/Excel.
     *
     * Contoh: ada 4 pengajuan, 2 disetujui; walau yang dicetak hanya 2 yang
     * disetujui (dicentang atau filter status), pembandingnya tetap 4.
     *
     * @param  \Illuminate\Http\Request $request  Request export (filter halaman ikut dikirim)
     * @return array{submitted: int, draft_count: int, approved_count: int, rejected_count: int}
     */
    public function getSubmissionSummary(Request $request): array
    {
        $counts = $this->buildFilteredQuery(new Request($request->only(['search', 'month', 'year'])))
            ->reorder()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'submitted' => (int) $counts->sum(),
            'draft_count' => (int) ($counts['draft'] ?? 0),
            'approved_count' => (int) ($counts['approved'] ?? 0),
            'rejected_count' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    /**
     * Teks status untuk header PDF/Excel (revisi klien: menggantikan "SEMUA").
     *
     * Menggambarkan rasio persetujuan terhadap SELURUH pengajuan sesuai filter
     * halaman (getSubmissionSummary), bukan hanya data yang dicetak:
     * - Tanpa filter status / data terpilih: "Disetujui 2 dari 4 yang diajukan"
     *   (2 = disetujui di antara data yang dicetak).
     * - Filter satu status: "DISETUJUI (2 dari 4 yang diajukan)".
     *
     * @param  \Illuminate\Http\Request        $request     Request export
     * @param  \Illuminate\Support\Collection  $reimburses  Data yang di-export
     * @param  array|null                       $summary     Hasil getSubmissionSummary (opsional)
     * @return string
     */
    public function buildStatusText(Request $request, Collection $reimburses, ?array $summary = null): string
    {
        $status = $request->input('status');
        $labels = ['draft' => 'DRAFT', 'approved' => 'DISETUJUI', 'rejected' => 'DITOLAK'];
        $submitted = ($summary ?? $this->getSubmissionSummary($request))['submitted'];

        // Data terpilih bisa berada di luar filter halaman (mis. filter diubah
        // setelah mencentang) — pembanding minimal sebanyak data yang dicetak.
        $submitted = max($submitted, $reimburses->count());

        if (!empty($this->selectedIds($request)) || !isset($labels[$status])) {
            $approved = $reimburses->where('status', 'approved')->count();

            return "Disetujui {$approved} dari {$submitted} yang diajukan";
        }

        return "{$labels[$status]} ({$reimburses->count()} dari {$submitted} yang diajukan)";
    }

    /**
     * Data reimburse yang mengikuti nota (dipakai saat buat & sinkron).
     *
     * - date               : tanggal nota
     * - project_name       : nama proyek nota (fallback ke "Kepada")
     * - expense_description: ringkasan item nota + kode nota
     * - total_amount       : total akhir yang tercetak di nota
     *                        (sewa/jual: termasuk PPN; proyek: tanpa PPN)
     *
     * @param  \App\Models\Administrasi\Nota  $nota
     * @return array<string, mixed>
     */
    private function buildNotaPayload(Nota $nota): array
    {
        return [
            'date' => $nota->nota_date?->format('Y-m-d'),
            'project_name' => $this->resolveNotaProjectName($nota),
            'expense_description' => $this->buildNotaDescription($nota),
            'total_amount' => (int) ($nota->total_with_ppn ?? $nota->jumlah_total ?? 0),
        ];
    }

    /**
     * Nama proyek reimburse dari nota.
     *
     * Nota proyek memakai nama_proyek; nota sewa/jual (tanpa nama proyek)
     * memakai label "Sewa/Jual - {Kepada}".
     *
     * @param  \App\Models\Administrasi\Nota  $nota
     * @return string
     */
    private function resolveNotaProjectName(Nota $nota): string
    {
        $namaProyek = trim((string) $nota->nama_proyek);
        $kepada = trim((string) $nota->kepada);

        if ($namaProyek !== '' && $namaProyek !== '-') {
            $label = $namaProyek;
        } elseif ($nota->tipe_nota === Nota::TIPE_PROYEK) {
            $label = $kepada !== '' ? $kepada : 'Nota Proyek';
        } else {
            $label = $kepada !== '' ? "Sewa/Jual - {$kepada}" : 'Nota Sewa/Jual';
        }

        return Str::substr($label, 0, 255);
    }

    /**
     * Ringkasan keterangan belanja dari item nota.
     *
     * Item dengan nama & satuan sama dijumlahkan, mis. tiga baris SEMEN zak
     * menjadi "SEMEN 30 zak (Nota NTP-012/AKI/26)". Untuk nota sewa/jual,
     * biaya tambahan yang terisi ikut disebut (mis. "Ongkos Kirim").
     *
     * @param  \App\Models\Administrasi\Nota  $nota
     * @return string
     */
    private function buildNotaDescription(Nota $nota): string
    {
        $groups = [];

        foreach ((array) $nota->items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $nama = trim((string) ($item['nama_barang'] ?? $item['name'] ?? ''));

            if ($nama === '') {
                continue;
            }

            $satuan = trim((string) ($item['satuan'] ?? ''));
            $key = mb_strtolower($nama.'|'.$satuan);

            $groups[$key] ??= ['nama' => $nama, 'satuan' => $satuan, 'qty' => 0];
            $groups[$key]['qty'] += (int) ($item['quantity'] ?? $item['banyaknya'] ?? 0);
        }

        $parts = array_map(function (array $group) {
            $qty = $group['qty'] > 0 ? number_format($group['qty'], 0, ',', '.') : '';

            return implode(' ', array_filter([$group['nama'], $qty, $group['satuan']], fn ($part) => $part !== ''));
        }, array_values($groups));

        if (count($parts) > self::NOTA_DESCRIPTION_MAX_ITEMS) {
            $remaining = count($parts) - self::NOTA_DESCRIPTION_MAX_ITEMS;
            $parts = array_slice($parts, 0, self::NOTA_DESCRIPTION_MAX_ITEMS);
            $parts[] = "dan {$remaining} item lainnya";
        }

        if ($nota->tipe_nota !== Nota::TIPE_PROYEK) {
            foreach (self::NOTA_FEE_LABELS as $field => $label) {
                if ((int) $nota->{$field} > 0) {
                    $parts[] = $label;
                }
            }
        }

        return empty($parts)
            ? "Nota {$nota->id_nota}"
            : implode(', ', $parts)." (Nota {$nota->id_nota})";
    }

    /**
     * Catatan penanda bahwa pengajuan dibuat otomatis dari nota.
     *
     * Nota proyek dari Invoice Semen ikut mencantumkan nomor invoice & DO.
     *
     * @param  \App\Models\Administrasi\Nota  $nota
     * @return string
     */
    private function buildNotaNotes(Nota $nota, bool $automatic = true): string
    {
        $sources = array_filter([
            $nota->invoice_number ? "Invoice Semen {$nota->invoice_number}" : null,
            $nota->do_no ? "DO {$nota->do_no}" : null,
        ]);

        return ($automatic ? 'Dibuat otomatis dari' : 'Diambil dari')." Nota {$nota->id_nota}"
            .(empty($sources) ? '' : ' ('.implode(', ', $sources).')')
            .'.';
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
