<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\Nota;
use App\Models\Sdm\Executive;
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
     * @param  int|null     $month   Filter bulan (opsional)
     * @param  int|null     $year    Filter tahun (opsional)
     * @param  string|null  $tipe    Filter tipe nota (sewa_jual|proyek, opsional)
     * @return LengthAwarePaginator Hasil pencarian dengan paginasi
     */
    public function getPaginated(?string $search, ?int $month = null, ?int $year = null, ?string $tipe = null): LengthAwarePaginator
    {
        return Nota::where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->when($month, fn ($query, $month) => $query->whereMonth('nota_date', $month))
            ->when($year, fn ($query, $year) => $query->whereYear('nota_date', $year))
            ->when($tipe, fn ($query, $tipe) => $query->where('tipe_nota', $tipe))
            ->latest('created_at')
            ->paginate(self::PER_PAGE);
    }

    /**
     * Mengambil seluruh data nota untuk ekspor PDF.
     *
     * @param  string|null  $search  Keyword pencarian (opsional)
     * @param  int|null     $month   Filter bulan (opsional)
     * @param  int|null     $year    Filter tahun (opsional)
     * @param  string|null  $tipe    Filter tipe nota (opsional)
     * @return Collection Koleksi seluruh data nota
     */
    public function getAllForExport(?string $search, ?int $month = null, ?int $year = null, ?string $tipe = null): Collection
    {
        return Nota::where('created_by', auth()->id())
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->when($month, fn ($query, $month) => $query->whereMonth('nota_date', $month))
            ->when($year, fn ($query, $year) => $query->whereYear('nota_date', $year))
            ->when($tipe, fn ($query, $tipe) => $query->where('tipe_nota', $tipe))
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
     * 1. Generate kode nota otomatis (NTA-001/AKI/26 atau NTP-001/AKI/26)
     * 2. Proses array items sesuai tipe nota
     * 3. Hitung total items
     * 4. Proses biaya tambahan opsional (khusus tipe sewa_jual)
     * 5. Hitung grand total (items + biaya tambahan)
     * 6. Hitung PPN (khusus tipe sewa_jual)
     * 7. Simpan ke database
     *
     * @param  array<string, mixed>  $validated  Data yang sudah divalidasi dari StoreNotaRequest
     * @return Nota Model nota yang baru dibuat
     */
    public function create(array $validated): Nota
    {
        $tipe = $validated['tipe_nota'] ?? Nota::TIPE_SEWA_JUAL;
        $isProyek = $tipe === Nota::TIPE_PROYEK;
        $notaCode = $isProyek ? Nota::generateProyekCode() : Nota::generateNotaCode();
        $location = $validated['location'] ?? self::DEFAULT_LOCATION;

        // Proses array items sesuai tipe
        $items = $this->processItems($validated, $isProyek);
        $itemsTotal = $this->calculateItemsTotal($items);

        // Biaya tambahan & PPN hanya untuk tipe sewa_jual
        $optionalFees = $isProyek ? $this->emptyOptionalFees() : $this->processOptionalFees($validated);
        $jumlahTotal = $itemsTotal + array_sum($optionalFees);

        $ppnPercentage = $isProyek ? 0 : InputNormalizer::normalizeDecimal($validated['ppn_percentage'] ?? self::DEFAULT_PPN_PERCENTAGE);
        $ppnAmount = (int) ($jumlahTotal * ($ppnPercentage / 100));
        $totalWithPpn = $jumlahTotal + $ppnAmount;
        $penandatangan = $isProyek ? $this->resolvePenandatangan($validated) : null;

        return Nota::create([
            'id_nota' => $notaCode,
            'tipe_nota' => $tipe,
            'nama_proyek' => $validated['nama_proyek'] ?? null,
            'location' => $location,
            'nota_date' => $validated['nota_date'],
            'periode_start' => $validated['periode_start'] ?? null,
            'periode_end' => $validated['periode_end'] ?? null,
            'kepada' => $validated['kepada'],
            'faktur_no' => $validated['faktur_no'] ?? null,
            'sj_no' => $validated['sj_no'] ?? null,
            'items' => $items,
            'penerima' => $validated['penerima'] ?? null,
            'penandatangan' => $penandatangan,
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
        $tipe = $validated['tipe_nota'] ?? $nota->tipe_nota ?? Nota::TIPE_SEWA_JUAL;
        $isProyek = $tipe === Nota::TIPE_PROYEK;
        $location = $validated['location'] ?? self::DEFAULT_LOCATION;

        // Proses array items sesuai tipe
        $items = $this->processItems($validated, $isProyek);
        $itemsTotal = $this->calculateItemsTotal($items);

        // Biaya tambahan & PPN hanya untuk tipe sewa_jual
        $optionalFees = $isProyek ? $this->emptyOptionalFees() : $this->processOptionalFees($validated);
        $jumlahTotal = $itemsTotal + array_sum($optionalFees);

        $ppnPercentage = $isProyek ? 0 : InputNormalizer::normalizeDecimal($validated['ppn_percentage'] ?? self::DEFAULT_PPN_PERCENTAGE);
        $ppnAmount = (int) ($jumlahTotal * ($ppnPercentage / 100));
        $totalWithPpn = $jumlahTotal + $ppnAmount;
        $penandatangan = $isProyek ? $this->resolvePenandatangan($validated) : null;

        $nota->update([
            'tipe_nota' => $tipe,
            'nama_proyek' => $validated['nama_proyek'] ?? $nota->nama_proyek,
            'location' => $location,
            'nota_date' => $validated['nota_date'],
            'periode_start' => $validated['periode_start'] ?? null,
            'periode_end' => $validated['periode_end'] ?? null,
            'kepada' => $validated['kepada'],
            'faktur_no' => $validated['faktur_no'] ?? null,
            'sj_no' => $validated['sj_no'] ?? null,
            'items' => $items,
            'penerima' => $validated['penerima'] ?? null,
            'penandatangan' => $penandatangan,
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
     * Membuat snapshot petinggi penanda tangan untuk blok "Hormat Kami"
     * pada PDF nota proyek.
     *
     * Data diambil dari tabel executives (id, name, position, signature_image)
     * milik user login, plus divisi dari tabel divisions. Hasil disimpan
     * sebagai JSON snapshot (penandatangan) agar dokumen tidak berubah
     * bila data petinggi/divisi diedit atau dihapus kemudian.
     *
     * @param  array<string, mixed>  $validated  Data dari form request
     * @return array<string, mixed>|null Snapshot petinggi, null bila tidak dipilih
     */
    private function resolvePenandatangan(array $validated): ?array
    {
        $petinggiId = $validated['petinggi_id'] ?? null;
        $divisi = $validated['divisi'] ?? null;

        if ($petinggiId) {
            $executive = Executive::where('created_by', auth()->id())
                ->find($petinggiId);

            if ($executive) {
                return [
                    'id' => (int) $executive->id,
                    'name' => $executive->name,
                    'position' => $executive->position,
                    'signature_image' => $executive->signature_image,
                    'divisi' => $divisi ?: null,
                ];
            }
        }

        // Petinggi tidak dipilih/kosong: simpan divisi saja agar tetap
        // terekam di snapshot.
        return $divisi ? [
            'id' => null,
            'name' => null,
            'position' => null,
            'signature_image' => null,
            'divisi' => $divisi,
        ] : null;
    }

    /**
     * Memproses array items dari form input.
     *
     * Tipe sewa_jual:
     * - item_banyaknya[]     -> banyaknya
     * - item_nama_barang[]   -> nama_barang
     * - item_harga_satuan[]  -> harga_satuan
     * - jumlah = banyaknya × harga_satuan
     *
     * Tipe proyek:
     * - item_quantity[]      -> quantity
     * - item_satuan[]        -> satuan
     * - item_nama_barang[]   -> nama_barang
     * - item_harga[]         -> harga
     * - jumlah = quantity × harga
     *
     * @param  array<string, mixed>  $validated  Data dari form request
     * @param  bool  $isProyek  true bila tipe nota proyek
     * @return array<int, array<string, mixed>> Array of items
     */
    private function processItems(array $validated, bool $isProyek = false): array
    {
        if ($isProyek) {
            return $this->processProyekItems($validated);
        }

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
     * Memproses array items untuk tipe nota proyek.
     *
     * @param  array<string, mixed>  $validated  Data dari form request
     * @return array<int, array<string, mixed>> Array of items proyek
     */
    private function processProyekItems(array $validated): array
    {
        $items = [];

        if (empty($validated['item_quantity'])) {
            return $items;
        }

        $quantity = $validated['item_quantity'] ?? [];
        $satuan = $validated['item_satuan'] ?? [];
        $namaBarang = $validated['item_nama_barang'] ?? [];
        $harga = $validated['item_harga'] ?? [];

        foreach ($quantity as $index => $qty) {
            if (!empty($qty) && !empty($namaBarang[$index])) {
                $hargaValue = InputNormalizer::normalizeCurrency($harga[$index] ?? 0);
                $jumlah = (int) $qty * $hargaValue;

                $items[] = [
                    'quantity' => (int) $qty,
                    'satuan' => $satuan[$index] ?? null,
                    'nama_barang' => $namaBarang[$index],
                    'harga' => $hargaValue,
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
     * Mengembalikan biaya tambahan kosong (khusus tipe proyek).
     *
     * @return array<string, null> Biaya tambahan semuanya null
     */
    private function emptyOptionalFees(): array
    {
        return [
            'sewa_jual' => null,
            'ongkos_kirim' => null,
            'bongkar_pasang' => null,
            'lembur' => null,
            'uang_jaminan' => null,
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
     * Pencarian dilakukan pada kolom: id_nota, nama_proyek, kepada, faktur_no, sj_no.
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
            ->orWhere('nama_proyek', 'like', "%{$escapedSearch}%")
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
