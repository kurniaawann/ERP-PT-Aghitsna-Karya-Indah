{{-- Satu baris item pada tabel repeater halaman form Invoice Alumunium.
     Variabel:
     - $row : array item (keterangan, volume, satuan, harga) dari item
              tersimpan atau old('items'); [] untuk baris kosong / <template>.

     Input baris SENGAJA tanpa atribut name: aluminium-invoices/form.js membaca
     nilainya saat submit lalu menyusun `items` sesuai format request yang lama
     (JSON saat tambah, items[i][field] saat edit).

     Hook JS (resources/js/pages/finance/aluminium-invoices/form.js):
     .item-row, .item-keterangan, .item-volume, .item-satuan, .item-harga,
     .item-total, .remove-row-btn. Gaya input diatur CSS .fi-repeater
     (resources/css/app.css). --}}
@php
    $row = is_array($row ?? null) ? $row : [];

    $volume = $row['volume'] ?? '';
    $harga = \App\Services\InputNormalizer::normalizeCurrency($row['harga'] ?? 0);
    $total = \App\Services\InputNormalizer::normalizeDecimal($volume === '' ? 0 : $volume) * $harga;
@endphp

<tr class="item-row">
    <td>
        <input type="text" class="item-keterangan" value="{{ $row['keterangan'] ?? '' }}" placeholder="Keterangan"
            aria-label="Keterangan" required
            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </td>

    <td>
        <input type="number" step="0.01" min="0" class="item-volume text-right" value="{{ $volume }}" placeholder="0"
            aria-label="Volume" required
            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </td>

    <td>
        <input type="text" class="item-satuan" value="{{ $row['satuan'] ?? '' }}" placeholder="m3, unit"
            aria-label="Satuan" required
            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </td>

    <td>
        <input type="text" inputmode="numeric" class="item-harga text-right"
            value="{{ $row === [] ? '' : number_format($harga, 0, ',', '.') }}" placeholder="0" aria-label="Harga" required
            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </td>

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
