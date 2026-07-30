<?php

namespace App\Services;

use App\Models\Administrasi\CashOutProof;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service layer untuk modul Bukti Kas Keluar.
 *
 * Kelas ini bertanggung jawab atas seluruh business logic modul bukti kas keluar,
 * termasuk pembuatan data, pembaruan data, pencarian, dan ekspor PDF.
 * Controller hanya menerima request dan mengembalikan response.
 */
class CashOutProofService
{
    /**
     * Default nama Direktur yang digunakan jika tidak diisi.
     */
    private const DEFAULT_DIRECTOR = 'Zulkarnain,ST.,MT';

    /**
     * Default nama Kabag Keuangan yang digunakan jika tidak diisi.
     */
    private const DEFAULT_FINANCE_HEAD = 'Kamila,AMK';

    /**
     * Karakter wildcard LIKE yang perlu di-escape untuk pencarian.
     */
    private const LIKE_WILDCARDS = ['%', '_'];

    /**
     * Mengambil data bukti kas keluar dengan filter pencarian dan paginasi.
     *
     * @param  string|null  $search  Keyword pencarian (bkk_no, cek_no, paid_to, description)
     * @return LengthAwarePaginator Hasil pencarian dengan paginasi
     */
    public function getPaginated(?string $search): LengthAwarePaginator
    {
        return CashOutProof::query()
            ->where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->paginate(15);
    }

    /**
     * Mengambil seluruh data bukti kas keluar untuk ekspor PDF.
     *
     * @param  string|null  $search  Keyword pencarian (opsional)
     * @return Collection Koleksi seluruh data bukti kas keluar
     */
    public function getAllForExport(?string $search): Collection
    {
        return CashOutProof::query()
            ->where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->get();
    }

    /**
     * Mengambil data bukti kas keluar berdasarkan array bkk_no untuk ekspor PDF.
     *
     * @param  array<int, string>  $bkkNos  Array nomor BKK yang dipilih
     * @return Collection Koleksi data bukti kas keluar yang dipilih
     */
    public function getByIds(array $bkkNos): Collection
    {
        return CashOutProof::whereIn('bkk_no', $bkkNos)
            ->where('created_by', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Membuat data bukti kas keluar baru.
     *
     * @param  array<string, mixed>  $validatedData  Data yang sudah divalidasi dari FormRequest
     * @return CashOutProof Model bukti kas keluar yang baru dibuat
     */
    public function create(array $validatedData): CashOutProof
    {
        $data = $this->prepareDataForSave($validatedData);

        // Auto-generate nomor BKK dan CEK
        $data['bkk_no'] = CashOutProof::generateBkkNo();
        $data['cek_no'] = CashOutProof::generateCekNo();
        $data['created_by'] = auth()->id();

        return CashOutProof::create($data);
    }

    /**
     * Memperbarui data bukti kas keluar yang sudah ada.
     *
     * @param  CashOutProof  $cashOutProof  Model bukti kas keluar yang akan diperbarui
     * @param  array<string, mixed>  $validatedData  Data yang sudah divalidasi dari FormRequest
     * @return CashOutProof Model bukti kas keluar yang sudah diperbarui
     */
    public function update(CashOutProof $cashOutProof, array $validatedData): CashOutProof
    {
        $data = $this->prepareDataForSave($validatedData);

        $cashOutProof->update($data);

        return $cashOutProof;
    }

    /**
     * Menyiapkan data sebelum disimpan ke database.
     *
     * Fungsi ini menangani:
     * - Normalisasi format mata uang pada field amount
     * - Pengisian default value untuk director dan finance_head jika kosong
     *
     * @param  array<string, mixed>  $data  Data input dari form
     * @return array<string, mixed> Data yang sudah disiapkan untuk disimpan
     */
    private function prepareDataForSave(array $data): array
    {
        // Normalisasi format mata uang (hilangkan karakter non-angka)
        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? null);

        // Set default untuk director jika kosong
        if (empty($data['director'])) {
            $data['director'] = self::DEFAULT_DIRECTOR;
        }

        // Set default untuk finance_head jika kosong
        if (empty($data['finance_head'])) {
            $data['finance_head'] = self::DEFAULT_FINANCE_HEAD;
        }

        return $data;
    }

    /**
     * Menerapkan filter pencarian pada query builder.
     *
     * Pencarian dilakukan pada kolom: bkk_no, cek_no, paid_to, description.
     * Karakter wildcard LIKE (% dan _) di-escape untuk mencegah hasil yang tidak diinginkan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  string  $search  Keyword pencarian
     * @return \Illuminate\Database\Eloquent\Builder Query builder yang sudah difilter
     */
    private function applySearchFilter($query, string $search)
    {
        $escapedSearch = $this->escapeLikeWildcards($search);

        return $query->where('bkk_no', 'like', "%{$escapedSearch}%")
            ->orWhere('cek_no', 'like', "%{$escapedSearch}%")
            ->orWhere('paid_to', 'like', "%{$escapedSearch}%")
            ->orWhere('description', 'like', "%{$escapedSearch}%");
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
