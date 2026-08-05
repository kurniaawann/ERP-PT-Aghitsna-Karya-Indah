<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\Kwintansi;
use App\Services\InputNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service layer untuk modul Kwitansi.
 *
 * Kelas ini bertanggung jawab atas seluruh business logic modul kwitansi,
 * termasuk pembuatan data, pembaruan data, pencarian, dan ekspor PDF.
 * Controller hanya menerima request dan mengembalikan response.
 */
class KwintansiService
{
    /**
     * Karakter wildcard LIKE yang perlu di-escape untuk pencarian.
     */
    private const LIKE_WILDCARDS = ['%', '_'];

    /**
     * Default lokasi kwitansi jika tidak diisi.
     */
    private const DEFAULT_LOCATION = 'Depok';

    /**
     * Jumlah item per halaman untuk paginasi.
     */
    private const PER_PAGE = 15;

    /**
     * Mengambil data kwitansi dengan filter pencarian dan paginasi.
     *
     * @param  string|null  $search  Keyword pencarian (id_kwintansi, received_from, payment_for)
     * @return LengthAwarePaginator Hasil pencarian dengan paginasi
     */
    public function getPaginated(?string $search): LengthAwarePaginator
    {
        return Kwintansi::with('paymentAccount')
            ->where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->paginate(self::PER_PAGE);
    }

    /**
     * Mengambil seluruh data kwitansi untuk ekspor PDF.
     *
     * @param  string|null  $search  Keyword pencarian (opsional)
     * @return Collection Koleksi seluruh data kwitansi
     */
    public function getAllForExport(?string $search): Collection
    {
        return Kwintansi::with('paymentAccount')
            ->where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->get();
    }

    /**
     * Mengambil data kwitansi berdasarkan array id_kwintansi untuk ekspor PDF.
     *
     * @param  array<int, string>  $ids  Array ID kwitansi yang dipilih
     * @return Collection Koleksi data kwitansi yang dipilih
     */
    public function getByIds(array $ids): Collection
    {
        return Kwintansi::with('paymentAccount')
            ->whereIn('id_kwintansi', $ids)
            ->where('created_by', auth()->id())
            ->latest('created_at')
            ->get();
    }

    /**
     * Membuat data kwitansi baru.
     *
     * @param  array<string, mixed>  $validatedData  Data yang sudah divalidasi
     * @param  bool  $includeBank  Apakah bank ditampilkan di PDF
     * @param  array<string, mixed>  $paymentMethods  Metode pembayaran yang dipilih (is_tunai, is_cheque, is_bilyet_giro)
     * @return Kwintansi Model kwitansi yang baru dibuat
     */
    public function create(array $validatedData, bool $includeBank = false, array $paymentMethods = []): Kwintansi
    {
        $validatedData['id_kwintansi'] = Kwintansi::generateKwintansiCode();
        $validatedData['location'] = $validatedData['location'] ?? self::DEFAULT_LOCATION;
        $validatedData['include_bank'] = $includeBank;
        $this->applyPaymentMethods($validatedData, $paymentMethods);
        $validatedData['amount'] = InputNormalizer::normalizeCurrency($validatedData['amount'] ?? 0);
        $validatedData['remaining'] = InputNormalizer::normalizeCurrency($validatedData['remaining'] ?? 0);
        $validatedData['created_by'] = auth()->id();

        return Kwintansi::create($validatedData);
    }

    /**
     * Memperbarui data kwitansi yang sudah ada.
     *
     * @param  Kwintansi  $kwintansi  Model kwitansi yang akan diperbarui
     * @param  array<string, mixed>  $validatedData  Data yang sudah divalidasi
     * @param  bool  $includeBank  Apakah bank ditampilkan di PDF
     * @param  array<string, mixed>  $paymentMethods  Metode pembayaran yang dipilih (is_tunai, is_cheque, is_bilyet_giro)
     * @return Kwintansi Model kwitansi yang sudah diperbarui
     */
    public function update(Kwintansi $kwintansi, array $validatedData, bool $includeBank = false, array $paymentMethods = []): Kwintansi
    {
        $validatedData['location'] = $validatedData['location'] ?? self::DEFAULT_LOCATION;
        $validatedData['include_bank'] = $includeBank;
        $this->applyPaymentMethods($validatedData, $paymentMethods);
        $validatedData['amount'] = InputNormalizer::normalizeCurrency($validatedData['amount'] ?? 0);
        $validatedData['remaining'] = InputNormalizer::normalizeCurrency($validatedData['remaining'] ?? 0);

        $kwintansi->update($validatedData);

        return $kwintansi;
    }

    /**
     * Menetapkan metode pembayaran (TUNAI, CHEQUE, BILYET GIRO) ke data validasi.
     *
     * @param  array<string, mixed>  $validatedData  Data yang sudah divalidasi
     * @param  array<string, mixed>  $paymentMethods  Metode pembayaran yang dipilih
     */
    private function applyPaymentMethods(array &$validatedData, array $paymentMethods): void
    {
        $validatedData['is_tunai'] = (bool) ($paymentMethods['is_tunai'] ?? false);
        $validatedData['is_cheque'] = (bool) ($paymentMethods['is_cheque'] ?? false);
        $validatedData['is_bilyet_giro'] = (bool) ($paymentMethods['is_bilyet_giro'] ?? false);
    }

    /**
     * Menghapus beberapa kwitansi sekaligus (bulk delete).
     *
     * @param  array  $ids  Daftar id_kwintansi yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        return Kwintansi::whereIn('id_kwintansi', $ids)
            ->where('created_by', auth()->id())
            ->delete();
    }

    /**
     * Menerapkan filter pencarian pada query builder.
     *
     * Pencarian dilakukan pada kolom: id_kwintansi, received_from, payment_for.
     * Karakter wildcard LIKE (% dan _) di-escape untuk mencegah hasil yang tidak diinginkan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  string  $search  Keyword pencarian
     * @return \Illuminate\Database\Eloquent\Builder Query builder yang sudah difilter
     */
    private function applySearchFilter($query, string $search)
    {
        $escapedSearch = $this->escapeLikeWildcards($search);

        return $query->where('id_kwintansi', 'like', "%{$escapedSearch}%")
            ->orWhere('received_from', 'like', "%{$escapedSearch}%")
            ->orWhere('payment_for', 'like', "%{$escapedSearch}%");
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
