<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\DocumentReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service layer untuk modul Tanda Terima Dokumen.
 *
 * Kelas ini bertanggung jawab atas seluruh business logic modul tanda terima dokumen,
 * termasuk pembuatan data, pembaruan data, pencarian, dan ekspor PDF.
 * Controller hanya menerima request dan mengembalikan response.
 */
class DocumentReceiptService
{
    /**
     * Karakter wildcard LIKE yang perlu di-escape untuk pencarian.
     */
    private const LIKE_WILDCARDS = ['%', '_'];

    /**
     * Default lokasi dokumen jika tidak diisi.
     */
    private const DEFAULT_LOCATION = 'Depok';

    /**
     * Jumlah item per halaman untuk paginasi.
     */
    private const PER_PAGE = 15;

    /**
     * Mengambil data tanda terima dokumen dengan filter pencarian dan paginasi.
     *
     * @param  string|null  $search  Keyword pencarian (id_document, received_from, regarding)
     * @param  int|null     $month   Filter bulan (opsional)
     * @param  int|null     $year    Filter tahun (opsional)
     * @return LengthAwarePaginator Hasil pencarian dengan paginasi
     */
    public function getPaginated(?string $search, ?int $month = null, ?int $year = null): LengthAwarePaginator
    {
        return DocumentReceipt::query()
            ->where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->when($month, fn ($query, $month) => $query->whereMonth('receipt_date', $month))
            ->when($year, fn ($query, $year) => $query->whereYear('receipt_date', $year))
            ->latest()
            ->paginate(self::PER_PAGE);
    }

    /**
     * Mengambil seluruh data tanda terima dokumen untuk ekspor PDF.
     *
     * @param  string|null  $search  Keyword pencarian (opsional)
     * @param  int|null     $month   Filter bulan (opsional)
     * @param  int|null     $year    Filter tahun (opsional)
     * @return Collection Koleksi seluruh data tanda terima dokumen
     */
    public function getAllForExport(?string $search, ?int $month = null, ?int $year = null): Collection
    {
        return DocumentReceipt::query()
            ->where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->when($month, fn ($query, $month) => $query->whereMonth('receipt_date', $month))
            ->when($year, fn ($query, $year) => $query->whereYear('receipt_date', $year))
            ->latest()
            ->get();
    }

    /**
     * Mengambil data tanda terima dokumen berdasarkan array id_document untuk ekspor PDF.
     *
     * @param  array<int, string>  $ids  Array ID dokumen yang dipilih
     * @return Collection Koleksi data tanda terima dokumen yang dipilih
     */
    public function getByIds(array $ids): Collection
    {
        return DocumentReceipt::whereIn('id_document', $ids)
            ->where('created_by', auth()->id())
            ->latest()
            ->get();
    }

    /**
     * Membuat data tanda terima dokumen baru.
     *
     * @param  array<string, mixed>  $validatedData  Data yang sudah divalidasi dari FormRequest
     * @return DocumentReceipt Model tanda terima dokumen yang baru dibuat
     */
    public function create(array $validatedData): DocumentReceipt
    {
        $validatedData['id_document'] = DocumentReceipt::generateDocumentCode();
        $validatedData['location'] = $validatedData['location'] ?? self::DEFAULT_LOCATION;
        $validatedData['created_by'] = auth()->id();

        return DocumentReceipt::create($validatedData);
    }

    /**
     * Memperbarui data tanda terima dokumen yang sudah ada.
     *
     * @param  DocumentReceipt  $documentReceipt  Model tanda terima dokumen yang akan diperbarui
     * @param  array<string, mixed>  $validatedData  Data yang sudah divalidasi dari FormRequest
     * @return DocumentReceipt Model tanda terima dokumen yang sudah diperbarui
     */
    public function update(DocumentReceipt $documentReceipt, array $validatedData): DocumentReceipt
    {
        unset($validatedData['id_document']);
        $validatedData['location'] = $validatedData['location'] ?? self::DEFAULT_LOCATION;

        $documentReceipt->update($validatedData);

        return $documentReceipt;
    }

    /**
     * Menghapus beberapa tanda terima dokumen sekaligus (bulk delete).
     *
     * @param  array  $ids  Daftar id_document yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        return DocumentReceipt::whereIn('id_document', $ids)
            ->where('created_by', auth()->id())
            ->delete();
    }

    /**
     * Menerapkan filter pencarian pada query builder.
     *
     * Pencarian dilakukan pada kolom: id_document, received_from, regarding.
     * Karakter wildcard LIKE (% dan _) di-escape untuk mencegah hasil yang tidak diinginkan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  string  $search  Keyword pencarian
     * @return \Illuminate\Database\Eloquent\Builder Query builder yang sudah difilter
     */
    private function applySearchFilter($query, string $search)
    {
        $escapedSearch = $this->escapeLikeWildcards($search);

        return $query->where('id_document', 'like', "%{$escapedSearch}%")
            ->orWhere('received_from', 'like', "%{$escapedSearch}%")
            ->orWhere('regarding', 'like', "%{$escapedSearch}%");
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
