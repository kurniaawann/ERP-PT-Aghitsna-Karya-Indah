<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\SuratPerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service layer untuk modul Surat Perintah Kerja (SPK).
 *
 * Kelas ini bertanggung jawab atas seluruh business logic modul surat perintah
 * kerja, termasuk pencarian, pembuatan data, pembaruan data, dan ekspor.
 * Controller hanya menerima request dan mengembalikan response.
 *
 * @package App\Services\Administrasi
 */
class SuratPerintahKerjaService
{
    /**
     * Karakter wildcard LIKE yang perlu di-escape untuk pencarian.
     */
    private const LIKE_WILDCARDS = ['%', '_'];

    /**
     * Jumlah data per halaman untuk paginasi.
     */
    private const PER_PAGE = 15;

    /**
     * Mengambil data SPK dengan filter pencarian dan paginasi.
     *
     * @param  string|null  $search  Keyword pencarian (nomor, proyek, lokasi, pemberi_tugas_nama)
     * @return LengthAwarePaginator Hasil pencarian dengan paginasi
     */
    public function getPaginated(?string $search): LengthAwarePaginator
    {
        return SuratPerintahKerja::query()
            ->where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->paginate(self::PER_PAGE);
    }

    /**
     * Membuat data SPK baru.
     *
     * Proses:
     * 1. Generate nomor SPK otomatis ({sequence}/SPK/AKI/DIV.PRODUKSI/{tahun})
     * 2. Proses array items (no, keterangan, volume, satuan, harga, jumlah)
     * 3. Hitung total amount
     * 4. Simpan ke database
     *
     * @param  array<string, mixed>  $validated  Data yang sudah divalidasi dari StoreSuratPerintahKerjaRequest
     * @return SuratPerintahKerja Model SPK yang baru dibuat
     */
    public function create(array $validated): SuratPerintahKerja
    {
        $nomor = SuratPerintahKerja::generateNomor();
        $items = $this->processItems($validated);
        $totalAmount = $this->calculateTotalAmount($items);

        return SuratPerintahKerja::create([
            'nomor' => $nomor,
            'proyek' => $validated['proyek'],
            'lokasi' => $validated['lokasi'],
            'tanggal' => $validated['tanggal'],
            'pemberi_tugas_nama' => $validated['pemberi_tugas_nama'],
            'pemberi_tugas_alamat' => $validated['pemberi_tugas_alamat'],
            'signer_nama' => $validated['signer_nama'],
            'signer_jabatan' => $validated['signer_jabatan'],
            'items' => $items,
            'total_amount' => $totalAmount,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Memperbarui data SPK yang sudah ada.
     *
     * Proses sama dengan create, tetapi memperbarui data existing (nomor tetap).
     *
     * @param  SuratPerintahKerja  $spk  Model SPK yang akan diperbarui
     * @param  array<string, mixed>  $validated  Data yang sudah divalidasi dari UpdateSuratPerintahKerjaRequest
     * @return SuratPerintahKerja Model SPK yang sudah diperbarui
     */
    public function update(SuratPerintahKerja $spk, array $validated): SuratPerintahKerja
    {
        $items = $this->processItems($validated);
        $totalAmount = $this->calculateTotalAmount($items);

        $spk->update([
            'proyek' => $validated['proyek'],
            'lokasi' => $validated['lokasi'],
            'tanggal' => $validated['tanggal'],
            'pemberi_tugas_nama' => $validated['pemberi_tugas_nama'],
            'pemberi_tugas_alamat' => $validated['pemberi_tugas_alamat'],
            'signer_nama' => $validated['signer_nama'],
            'signer_jabatan' => $validated['signer_jabatan'],
            'items' => $items,
            'total_amount' => $totalAmount,
        ]);

        return $spk;
    }

    /**
     * Memproses array items dari form input (struktur grup No/Kode).
     *
     * Form menggunakan nama:
     * - no[gi], kode[gi]                      (satu per grup No/Kode)
     * - detail_keterangan[gi][], detail_volume[gi][], detail_satuan[gi][],
     *   detail_harga[gi][], detail_jumlah[gi][] (beberapa per grup)
     *
     * Diubah menjadi struktur grouped:
     * - no (int): nomor urut grup
     * - kode (string): kode pekerjaan
     * - details (array): beberapa baris keterangan, tiap baris memiliki
     *   keterangan, volume, satuan, harga, jumlah (volume x harga)
     *
     * @param  array<string, mixed>  $validated  Data dari form request
     * @return array<int, array<string, mixed>> Array of groups
     */
    private function processItems(array $validated): array
    {
        $items = [];

        $nos = $validated['no'] ?? [];
        $kodes = $validated['kode'] ?? [];
        $ketGroups = $validated['detail_keterangan'] ?? [];
        $volGroups = $validated['detail_volume'] ?? [];
        $satGroups = $validated['detail_satuan'] ?? [];
        $harGroups = $validated['detail_harga'] ?? [];

        foreach ($ketGroups as $gi => $keterangans) {
            if (empty($keterangans)) {
                continue;
            }

            $details = [];
            $volumes = $volGroups[$gi] ?? [];
            $satuans = $satGroups[$gi] ?? [];
            $hargas = $harGroups[$gi] ?? [];

            foreach ($keterangans as $di => $keterangan) {
                if (empty($keterangan) || !isset($volumes[$di]) || !isset($hargas[$di])) {
                    continue;
                }

                $volume = (float) $volumes[$di];
                $harga = (float) $hargas[$di];

                $details[] = [
                    'keterangan' => $keterangan,
                    'volume' => $volume,
                    'satuan' => $satuans[$di] ?? '',
                    'harga' => $harga,
                    'jumlah' => round($volume * $harga, 2),
                ];
            }

            if (count($details) > 0) {
                $items[] = [
                    'no' => (int) ($nos[$gi] ?? $gi + 1),
                    'kode' => $kodes[$gi] ?? '',
                    'details' => $details,
                ];
            }
        }

        return $items;
    }

    /**
     * Menghitung total amount seluruh detail items (jumlah = volume x harga).
     *
     * @param  array<int, array<string, mixed>>  $items  Array of groups
     * @return float Total amount seluruh items
     */
    private function calculateTotalAmount(array $items): float
    {
        $total = 0;
        foreach ($items as $item) {
            foreach ($item['details'] ?? [] as $detail) {
                $total += $detail['jumlah'] ?? 0;
            }
        }
        return round($total, 2);
    }

    /**
     * Menghapus beberapa SPK sekaligus (bulk delete).
     *
     * @param  array  $ids  Daftar nomor SPK yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        return SuratPerintahKerja::whereIn('nomor', $ids)
            ->where('created_by', auth()->id())
            ->delete();
    }

    /**
     * Menerapkan filter pencarian pada query builder.
     *
     * Karakter wildcard LIKE (% dan _) di-escape untuk mencegah hasil yang tidak diinginkan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  string  $search  Keyword pencarian
     * @return \Illuminate\Database\Eloquent\Builder Query builder yang sudah difilter
     */
    private function applySearchFilter($query, string $search)
    {
        $escapedSearch = $this->escapeLikeWildcards($search);

        return $query->where('nomor', 'like', "%{$escapedSearch}%")
            ->orWhere('proyek', 'like', "%{$escapedSearch}%")
            ->orWhere('lokasi', 'like', "%{$escapedSearch}%")
            ->orWhere('pemberi_tugas_nama', 'like', "%{$escapedSearch}%");
    }

    /**
     * Meng-escape karakter wildcard LIKE untuk mencegah hasil pencarian yang tidak diinginkan.
     *
     * @param  string  $value  Nilai yang akan di-escape
     * @return string Nilai yang sudah di-escape
     */
    private function escapeLikeWildcards(string $value): string
    {
        foreach (self::LIKE_WILDCARDS as $wildcard) {
            $value = str_replace($wildcard, '\\'.$wildcard, $value);
        }

        return $value;
    }
}
