<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\Nota;
use App\Services\InputNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service layer untuk modul Nota Administrasi.
 *
 * Kelas ini bertanggung jawab atas seluruh business logic modul nota,
 * termasuk pembuatan data, pembaruan data, pencarian, dan ekspor PDF.
 * Controller hanya menerima request dan mengembalikan response.
 */
class NotaService
{
    /**
     * Karakter wildcard LIKE yang perlu di-escape untuk pencarian.
     */
    private const LIKE_WILDCARDS = ['%', '_'];

    /**
     * Default lokasi nota jika tidak diisi.
     */
    private const DEFAULT_LOCATION = 'Jakarta';

    /**
     * Jumlah item per halaman untuk paginasi.
     */
    private const PER_PAGE = 15;

    /**
     * Persentase PPN default (%).
     */
    private const DEFAULT_PPN_PERCENTAGE = 12;

    /**
     * Mengambil data nota dengan filter pencarian dan paginasi.
     *
     * @param  string|null  $search  Keyword pencarian (id_nota, kepada, faktur_no, sj_no)
     * @return LengthAwarePaginator Hasil pencarian dengan paginasi
     */
    public function getPaginated(?string $search): LengthAwarePaginator
    {
        return Nota::where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->paginate(self::PER_PAGE);
    }

    /**
     * Mengambil seluruh data nota untuk ekspor PDF.
     *
     * @param  string|null  $search  Keyword pencarian (opsional)
     * @return Collection Koleksi seluruh data nota
     */
    public function getAllForExport(?string $search): Collection
    {
        return Nota::where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->get();
    }

    /**
     * Mengambil data nota berdasarkan array id_nota untuk ekspor PDF.
     *
     * @param  array<int, string>  $ids  Array ID nota yang dipilih
     * @return Collection Koleksi data nota yang dipilih
     */
    public function getByIds(array $ids): Collection
    {
        return Nota::whereIn('id_nota', $ids)
            ->where('created_by', auth()->id())
            ->latest('created_at')
            ->get();
    }

    /**
     * Membuat data nota baru.
     *
     * Proses:
     * 1. Generate kode nota otomatis (NTA-001/AKI/26)
     * 2. Proses array items (qty, nama_barang, harga_satuan, jumlah)
     * 3. Hitung total items
     * 4. Proses biaya tambahan opsional
     * 5. Hitung grand total (items + biaya tambahan)
     * 6. Hitung PPN
     * 7. Simpan ke database
     *
     * @param  array<string, mixed>  $validated  Data yang sudah divalidasi dari StoreNotaRequest
     * @return Nota Model nota yang baru dibuat
     */
    public function create(array $validated): Nota
    {
        $notaCode = Nota::generateNotaCode();
        $location = $validated['location'] ?? self::DEFAULT_LOCATION;

        // Proses array items
        $items = $this->processItems($validated);
        $itemsTotal = $this->calculateItemsTotal($items);

        // Proses biaya tambahan opsional
        $optionalFees = $this->processOptionalFees($validated);
        $jumlahTotal = $itemsTotal + array_sum($optionalFees);

        // Proses PPN
        $ppnPercentage = InputNormalizer::normalizeDecimal($validated['ppn_percentage'] ?? self::DEFAULT_PPN_PERCENTAGE);
        $ppnAmount = (int) ($jumlahTotal * ($ppnPercentage / 100));
        $totalWithPpn = $jumlahTotal + $ppnAmount;

        return Nota::create([
            'id_nota' => $notaCode,
            'location' => $location,
            'nota_date' => $validated['nota_date'],
            'kepada' => $validated['kepada'],
            'faktur_no' => $validated['faktur_no'],
            'sj_no' => $validated['sj_no'],
            'items' => $items,
            'penerima' => $validated['penerima'] ?? null,
            'sewa_jual' => $optionalFees['sewa_jual'],
            'ongkos_kirim' => $optionalFees['ongkos_kirim'],
            'bongkar_pasang' => $optionalFees['bongkar_pasang'],
            'lembur' => $optionalFees['lembur'],
            'uang_jaminan' => $optionalFees['uang_jaminan'],
            'jumlah_total' => $jumlahTotal,
            'selected_payment_accounts' => $validated['selected_payment_accounts'] ?? [],
            'ppn_percentage' => $ppnPercentage,
            'ppn_amount' => $ppnAmount,
            'total_with_ppn' => $totalWithPpn,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Memperbarui data nota yang sudah ada.
     *
     * Proses sama dengan create, tetapi memperbarui data existing.
     *
     * @param  Nota  $nota  Model nota yang akan diperbarui
     * @param  array<string, mixed>  $validated  Data yang sudah divalidasi dari UpdateNotaRequest
     * @return Nota Model nota yang sudah diperbarui
     */
    public function update(Nota $nota, array $validated): Nota
    {
        $location = $validated['location'] ?? self::DEFAULT_LOCATION;

        // Proses array items
        $items = $this->processItems($validated);
        $itemsTotal = $this->calculateItemsTotal($items);

        // Proses biaya tambahan opsional
        $optionalFees = $this->processOptionalFees($validated);
        $jumlahTotal = $itemsTotal + array_sum($optionalFees);

        // Proses PPN
        $ppnPercentage = InputNormalizer::normalizeDecimal($validated['ppn_percentage'] ?? self::DEFAULT_PPN_PERCENTAGE);
        $ppnAmount = (int) ($jumlahTotal * ($ppnPercentage / 100));
        $totalWithPpn = $jumlahTotal + $ppnAmount;

        $nota->update([
            'location' => $location,
            'nota_date' => $validated['nota_date'],
            'kepada' => $validated['kepada'],
            'faktur_no' => $validated['faktur_no'],
            'sj_no' => $validated['sj_no'],
            'items' => $items,
            'penerima' => $validated['penerima'] ?? null,
            'sewa_jual' => $optionalFees['sewa_jual'],
            'ongkos_kirim' => $optionalFees['ongkos_kirim'],
            'bongkar_pasang' => $optionalFees['bongkar_pasang'],
            'lembur' => $optionalFees['lembur'],
            'uang_jaminan' => $optionalFees['uang_jaminan'],
            'jumlah_total' => $jumlahTotal,
            'selected_payment_accounts' => $validated['selected_payment_accounts'] ?? [],
            'ppn_percentage' => $ppnPercentage,
            'ppn_amount' => $ppnAmount,
            'total_with_ppn' => $totalWithPpn,
        ]);

        return $nota;
    }

    /**
     * Memproses array items dari form input.
     *
     * Mengambil data item_banyaknya[], item_nama_barang[], item_harga_satuan[]
     * dan mengubahnya menjadi array of items dengan struktur:
     * - banyaknya (int): jumlah qty
     * - nama_barang (string): nama barang
     * - harga_satuan (int): harga satuan (sudah dinormalisasi)
     * - jumlah (int): total per item (banyaknya × harga_satuan)
     *
     * @param  array<string, mixed>  $validated  Data dari form request
     * @return array<int, array<string, mixed>> Array of items
     */
    private function processItems(array $validated): array
    {
        $items = [];

        if (empty($validated['item_banyaknya'])) {
            return $items;
        }

        $banyaknya = $validated['item_banyaknya'] ?? [];
        $namaBarang = $validated['item_nama_barang'] ?? [];
        $hargaSatuan = $validated['item_harga_satuan'] ?? [];

        foreach ($banyaknya as $index => $qty) {
            if (!empty($qty) && !empty($namaBarang[$index])) {
                $harga = InputNormalizer::normalizeCurrency($hargaSatuan[$index] ?? 0);
                $jumlah = (int) $qty * $harga;

                $items[] = [
                    'banyaknya' => (int) $qty,
                    'nama_barang' => $namaBarang[$index],
                    'harga_satuan' => $harga,
                    'jumlah' => $jumlah,
                ];
            }
        }

        return $items;
    }

    /**
     * Menghitung total seluruh items.
     *
     * @param  array<int, array<string, mixed>>  $items  Array of items
     * @return int Total jumlah seluruh items
     */
    private function calculateItemsTotal(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['jumlah'] ?? 0;
        }
        return $total;
    }

    /**
     * Memproses biaya tambahan opsional dari form input.
     *
     * Field opsional:
     * - sewa_jual: Biaya sewa/jualan
     * - ongkos_kirim: Biaya ongkos kirim
     * - bongkar_pasang: Biaya bongkar/pasang
     * - lembur: Biaya lembur antar/ambil
     * - uang_jaminan: Biaya uang jaminan
     *
     * @param  array<string, mixed>  $validated  Data dari form request
     * @return array<string, int|null> Biaya tambahan yang sudah dinormalisasi
     */
    private function processOptionalFees(array $validated): array
    {
        return [
            'sewa_jual' => !empty($validated['sewa_jual']) ? InputNormalizer::normalizeCurrency($validated['sewa_jual']) : null,
            'ongkos_kirim' => !empty($validated['ongkos_kirim']) ? InputNormalizer::normalizeCurrency($validated['ongkos_kirim']) : null,
            'bongkar_pasang' => !empty($validated['bongkar_pasang']) ? InputNormalizer::normalizeCurrency($validated['bongkar_pasang']) : null,
            'lembur' => !empty($validated['lembur']) ? InputNormalizer::normalizeCurrency($validated['lembur']) : null,
            'uang_jaminan' => !empty($validated['uang_jaminan']) ? InputNormalizer::normalizeCurrency($validated['uang_jaminan']) : null,
        ];
    }

    /**
     * Menghapus beberapa nota sekaligus (bulk delete).
     *
     * @param  array  $ids  Daftar id_nota yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        return Nota::whereIn('id_nota', $ids)
            ->where('created_by', auth()->id())
            ->delete();
    }

    /**
     * Menerapkan filter pencarian pada query builder.
     *
     * Pencarian dilakukan pada kolom: id_nota, kepada, faktur_no, sj_no.
     * Karakter wildcard LIKE (% dan _) di-escape untuk mencegah hasil yang tidak diinginkan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  string  $search  Keyword pencarian
     * @return \Illuminate\Database\Eloquent\Builder Query builder yang sudah difilter
     */
    private function applySearchFilter($query, string $search)
    {
        $escapedSearch = $this->escapeLikeWildcards($search);

        return $query->where('id_nota', 'like', "%{$escapedSearch}%")
            ->orWhere('kepada', 'like', "%{$escapedSearch}%")
            ->orWhere('faktur_no', 'like', "%{$escapedSearch}%")
            ->orWhere('sj_no', 'like', "%{$escapedSearch}%");
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
