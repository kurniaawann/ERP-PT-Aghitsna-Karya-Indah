<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\Nota;
use App\Models\Sdm\Executive;
use App\Services\InputNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
     * 8. Nota proyek dari Invoice Semen (Super Admin) otomatis membuat
     *    pengajuan reimburse draft (NotaObserver) dalam satu transaksi; nota
     *    dari "Tambah Nota" tidak (dipilih manual di Reimbursement)
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
        $penandatangan = $this->resolvePenandatangan($validated);

        return DB::transaction(fn () => Nota::create([
            'id_nota' => $notaCode,
            'invoice_number' => $validated['invoice_number'] ?? null,
            'do_no' => $validated['do_no'] ?? null,
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
        ]));
    }

    /**
     * Membuat satu nota proyek otomatis dari invoice semen.
     *
     * Dipanggil saat invoice semen dibuat dari DO Semen. Satu proyek dalam
     * invoice = satu nota proyek. Setiap baris item nota memakai data semen
     * (qty zak, satuan zak, nama SEMEN, harga per zak).
     *
     * @param  string  $doNo           Nomor DO Semen sumber.
     * @param  string  $invoiceNumber  Nomor invoice semen.
     * @param  array<string, mixed>  $project  Satu proyek dari projects invoice:
     *                                  {nama_proyek, pengurus_proyek, payment_account_id, items}.
     * @param  array<string, mixed>  $invoiceData  Data invoice: {invoice_date, signed_by_id, ...}.
     * @return Nota Nota proyek yang dibuat.
     */
    public function createProyekNotaForInvoice(string $doNo, string $invoiceNumber, array $project, array $invoiceData): Nota
    {
        $items = collect($project['items'] ?? []);
        $paymentAccountId = $project['payment_account_id'] ?? null;
        $pengurus = $project['pengurus_proyek'] ?? null;

        return $this->create([
            'tipe_nota' => Nota::TIPE_PROYEK,
            'nama_proyek' => $project['nama_proyek'] ?? '-',
            'nota_date' => $invoiceData['invoice_date'],
            'kepada' => $pengurus ?: ($project['nama_proyek'] ?? '-'),
            'item_quantity' => $items->pluck('qty')->all(),
            'item_satuan' => $items->map(fn () => 'zak')->all(),
            'item_nama_barang' => $items->pluck('nama_barang')->all(),
            'item_harga' => $items->pluck('harga')->all(),
            'penerima' => $pengurus,
            'petinggi_id' => $invoiceData['signed_by_id'] ?? null,
            'divisi' => null,
            'selected_payment_accounts' => $paymentAccountId ? [(int) $paymentAccountId] : [],
            'invoice_number' => $invoiceNumber,
            'do_no' => $doNo,
        ]);
    }

    /**
     * Memperbarui data nota yang sudah ada.
     *
     * Proses sama dengan create, tetapi memperbarui data existing.
     * Reimburse otomatis yang tertaut ikut disinkronkan selama masih draft
     * (NotaObserver) dalam transaksi yang sama.
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
        $penandatangan = $this->resolvePenandatangan($validated);

        DB::transaction(fn () => $nota->update([
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
        ]));

        return $nota;
    }

    /**
     * Jumlah nota (sesuai filter halaman) yang tanda tangannya belum lengkap
     * (Penerima / Hormat Kami) — dipakai tombol "Export Semua (PDF)" agar
     * meminta tanda tangan dulu.
     */
    public function countUnsigned(?string $search, ?int $month = null, ?int $year = null, ?string $tipe = null): int
    {
        return $this->getAllForExport($search, $month, $year, $tipe)
            ->reject(fn (Nota $nota) => $nota->isSigned())
            ->count();
    }

    /**
     * Rincian nota yang tanda tangannya belum lengkap, untuk modal
     * "Lengkapi Tanda Tangan Nota" — termasuk isian yang sudah ada agar
     * ditampilkan terkunci di modal.
     *
     * @param  Collection<int, Nota>  $notas
     * @return array<int, array<string, mixed>>
     */
    public function unsignedDetails(Collection $notas): array
    {
        return $notas
            ->reject(fn (Nota $nota) => $nota->isSigned())
            ->map(fn (Nota $nota) => [
                'id_nota' => $nota->id_nota,
                'tipe' => $nota->tipe_nota,
                'kepada' => $nota->kepada,
                'penerima' => $nota->penerima,
                'signer_id' => $nota->penandatangan['id'] ?? null,
                'signer_name' => $nota->penandatangan['name'] ?? null,
                'divisi' => $nota->penandatangan['divisi'] ?? null,
                'needs_signer' => ! $nota->hasSigner(),
                'needs_receiver' => ! $nota->hasReceiver(),
            ])
            ->values()
            ->all();
    }

    /**
     * Melengkapi tanda tangan nota yang belum lengkap (revisi klien: kedua
     * tanda tangan — Penerima/Tanda Terima & Hormat Kami — opsional saat nota
     * dibuat, tetapi wajib sebelum di-download). Bagian yang sudah terisi
     * tidak diubah.
     *
     * $signatures[id_nota] = ['penerima' => ..., 'petinggi_id' => ..., 'divisi' => ...]
     * - Hormat Kami kosong → diisi petinggi terpilih (+ divisi).
     * - Penerima kosong    → diisi nama penerima.
     *
     * Semua nota divalidasi dulu; bila masih ada yang belum lengkap tidak ada
     * yang disimpan dan daftar id_nota-nya dikembalikan. Disimpan tanpa event
     * model (saveQuietly) karena hanya blok tanda tangan yang berubah —
     * reimburse draft tertaut (NotaObserver) tidak perlu disinkron.
     *
     * @param  Collection<int, Nota>  $notas
     * @param  array<string, array<string, mixed>>  $signatures
     * @return array{signed: int, incomplete: array<int, string>}
     */
    public function completeSignatures(Collection $notas, array $signatures): array
    {
        $pending = [];
        $incomplete = [];

        foreach ($notas as $nota) {
            if ($nota->isSigned()) {
                continue;
            }

            $input = (array) ($signatures[$nota->id_nota] ?? []);
            $changes = [];

            if (! $nota->hasSigner()) {
                $petinggiId = (int) ($input['petinggi_id'] ?? 0);
                $snapshot = $petinggiId > 0 ? $this->resolvePenandatangan([
                    'petinggi_id' => $petinggiId,
                    // Divisi yang sudah tersimpan dipertahankan bila tidak diisi
                    'divisi' => ($input['divisi'] ?? null) ?: ($nota->penandatangan['divisi'] ?? null),
                ]) : null;

                if (empty($snapshot['name'])) {
                    $incomplete[] = $nota->id_nota;

                    continue;
                }

                $changes['penandatangan'] = $snapshot;
            }

            if (! $nota->hasReceiver()) {
                $receiver = trim((string) ($input['penerima'] ?? ''));

                if ($receiver === '') {
                    $incomplete[] = $nota->id_nota;

                    continue;
                }

                $changes['penerima'] = mb_substr($receiver, 0, 255);
            }

            $pending[] = [$nota, $changes];
        }

        if (! empty($incomplete)) {
            return ['signed' => 0, 'incomplete' => $incomplete];
        }

        DB::transaction(function () use ($pending) {
            foreach ($pending as [$nota, $changes]) {
                $nota->forceFill($changes)->saveQuietly();
            }
        });

        return ['signed' => count($pending), 'incomplete' => []];
    }

    /**
     * Membuat snapshot petinggi penanda tangan untuk blok "Hormat Kami"
     * pada PDF nota (proyek maupun sewa/jual).
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
     * Reimburse otomatis tertaut yang masih draft ikut dihapus oleh
     * NotaBuilder::delete(); yang sudah disetujui/ditolak tetap disimpan.
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
