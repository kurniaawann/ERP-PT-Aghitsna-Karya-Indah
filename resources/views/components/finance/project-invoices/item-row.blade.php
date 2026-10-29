{{-- Satu baris item pada tabel repeater halaman form Invoice Proyek.
     Variabel:
     - $row   : array item dari item tersimpan atau old('items'); [] untuk
                baris kosong / <template>.
     - $admin : true → format admin (deskripsi, harga, persentase opsional);
                false → format superadmin (keterangan, volume & satuan
                opsional, harga).

     Input baris SENGAJA tanpa atribut name: project-invoices/form.js membaca
     nilainya saat submit lalu menyusun `items` sesuai format request yang lama
     (JSON saat tambah, items[i][field] saat edit).

     Hook JS (resources/js/pages/finance/project-invoices/form.js):
     .item-row, .item-deskripsi, .item-persentase, .item-keterangan,
     .item-volume, .item-satuan, .item-harga, .item-total, .remove-row-btn.
     Gaya input diatur CSS .fi-repeater (resources/css/app.css). --}}
@php
    $row = is_array($row ?? null) ? $row : [];
    $isEmptyRow = $row === [];

    $harga = \App\Services\InputNormalizer::normalizeCurrency($row['harga'] ?? 0);

    // Persentase (admin): angka tersimpan → tanpa nol berlebih (12.50 → 12.5); input lama apa adanya
    $persentase = $row['persentase'] ?? null;
    $persentaseValue = $persentase === null || $persentase === ''
        ? ''
        : (is_numeric($persentase) ? rtrim(rtrim(number_format((float) $persentase, 2, '.', ''), '0'), '.') : $persentase);

    // Volume (superadmin): kosong → item borongan (Jumlah = Harga)
    $volumeValue = \App\Models\Finance\InvoiceProyek::hasEmptyVolume($row)
        ? ''
        : (is_numeric($row['volume']) ? (float) $row['volume'] : $row['volume']);

    // Jumlah awal (dihitung ulang form.js saat halaman dimuat)
    $total = $isEmptyRow ? 0 : \App\Models\Finance\InvoiceProyek::itemAmount(array_merge($row, ['harga' => $harga]));
@endphp

<tr class="item-row">
    @if ($admin)
        <td>
            <input type="text" class="item-deskripsi" value="{{ $row['deskripsi'] ?? '' }}" placeholder="Deskripsi pekerjaan"
                aria-label="Deskripsi" required
                oninvalid="this.setCustomValidity('Deskripsi tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </td>

        <td>
            <input type="text" inputmode="numeric" class="item-harga text-right"
                value="{{ $isEmptyRow ? '' : number_format($harga, 0, ',', '.') }}" placeholder="0" aria-label="Harga" required
                oninvalid="this.setCustomValidity('Harga tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </td>

        {{-- Persentase opsional: kosong → Jumlah = Harga --}}
        <td>
            <input type="text" inputmode="decimal" class="item-persentase text-right" value="{{ $persentaseValue }}"
                placeholder="Opsional" aria-label="Persentase (%)" title="Kosongkan bila Jumlah = Harga">
        </td>
    @else
        <td>
            <input type="text" class="item-keterangan" value="{{ $row['keterangan'] ?? '' }}" placeholder="Keterangan"
                aria-label="Keterangan" required
                oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </td>

        {{-- Volume & satuan opsional: volume kosong → item borongan (Jumlah = Harga) --}}
        <td>
            <input type="number" step="0.01" min="0" class="item-volume text-right" value="{{ $volumeValue }}"
                placeholder="–" aria-label="Volume (opsional)" title="Opsional — kosongkan bila Jumlah = Harga (borongan)"
                oninput="this.setCustomValidity('')">
        </td>

        <td>
            <input type="text" class="item-satuan" value="{{ $row['satuan'] ?? '' }}" placeholder="–"
                aria-label="Satuan (opsional)" title="Opsional">
        </td>

        <td>
            <input type="text" inputmode="numeric" class="item-harga text-right"
                value="{{ $isEmptyRow ? '' : number_format($harga, 0, ',', '.') }}" placeholder="0" aria-label="Harga" required
                oninvalid="this.setCustomValidity('Harga tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </td>
    @endif

    <td class="item-total whitespace-nowrap !ps-4 text-end text-sm font-medium tabular-nums text-gray-950">
        Rp {{ number_format($total, 0, ',', '.') }}
    </td>

    <td class="text-center">
        <button type="button" class="remove-row-btn" title="Hapus item">
            <x-fi.icon name="trash" class="h-5 w-5" />
            <span class="sr-only">Hapus item</span>
        </button>
    </td>
</tr>
