{{-- Satu baris item pada tabel repeater halaman form Invoice Barang.
     Variabel:
     - $row : array item (name_item, quantity, satuan, capital_price,
              selling_price, from_stock, id_item) dari item tersimpan atau
              old('items'); [] untuk baris kosong / <template>.

     Input baris SENGAJA tanpa atribut name: item-invoices/form.js membaca
     nilainya saat submit lalu menyusun `items` (JSON saat tambah,
     items[i][field] saat edit) sesuai format request yang lama.

     Hook JS (resources/js/pages/finance/item-invoices/form.js):
     .invoice-item-row, .item-from-stock, .item-picker, .item-search,
     .item-id, .item-name-field, .item-name, .item-stock-info, .item-qty, .item-satuan,
     .item-capital, .item-selling, .item-price-warning, .item-total,
     .remove-row-btn. Gaya input diatur CSS .fi-repeater (resources/css/app.css). --}}
@php
    $row = is_array($row ?? null) ? $row : [];

    $fromStock = filter_var($row['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $name = (string) ($row['name_item'] ?? '');
    $capital = \App\Services\InputNormalizer::normalizeCurrency($row['capital_price'] ?? 0);
    $selling = \App\Services\InputNormalizer::normalizeCurrency($row['selling_price'] ?? 0);
    // Baris baru: harga kosong (placeholder); baris berisi data: tampilkan apa adanya (termasuk Rp 0)
    $formatPrice = fn (int $value) => $row === [] ? '' : 'Rp ' . number_format($value, 0, ',', '.');
@endphp

<tr class="invoice-item-row">
    {{-- Barang: pilih dari stok (pencarian) atau ketik nama manual --}}
    <td>
        <div class="grid gap-y-1.5">
            <div @class(['item-picker relative', 'hidden' => ! $fromStock])>
                <input type="text" class="item-search !pe-8" value="{{ $fromStock ? $name : '' }}"
                    placeholder="Cari barang di stok..." autocomplete="off" role="combobox"
                    aria-autocomplete="list" aria-expanded="false" aria-label="Cari barang dari stok">
                <x-fi.icon name="chevron-down" class="pointer-events-none absolute end-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            </div>
            <input type="hidden" class="item-id" value="{{ $fromStock ? ($row['id_item'] ?? '') : '' }}">

            {{-- Dibungkus div: display input diatur CSS .fi-repeater, jadi class hidden dipasang di pembungkus --}}
            <div @class(['item-name-field', 'hidden' => $fromStock])>
                <input type="text" class="item-name" value="{{ $fromStock ? '' : $name }}"
                    placeholder="Nama barang" maxlength="255" aria-label="Nama barang" @required(! $fromStock)
                    oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')" oninput="this.setCustomValidity('')">
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                <label class="inline-flex cursor-pointer items-center gap-x-2 text-sm text-gray-600">
                    <input type="checkbox" class="item-from-stock fi-checkbox" @checked($fromStock)>
                    Dari stok
                </label>
                <span class="item-stock-info text-sm text-gray-500"></span>
            </div>
        </div>
    </td>

    <td>
        <input type="number" class="item-qty text-center" value="{{ max(1, (int) ($row['quantity'] ?? 1)) }}" min="1"
            required aria-label="Qty"
            oninvalid="this.setCustomValidity('Qty minimal 1')" oninput="this.setCustomValidity('')">
    </td>

    <td>
        <input type="text" class="item-satuan" value="{{ $row['satuan'] ?? '' }}" placeholder="pcs" maxlength="50"
            aria-label="Satuan" title="Satuan barang, contoh: zak, batang, pcs">
    </td>

    <td>
        <input type="text" inputmode="numeric" class="item-capital text-right" value="{{ $formatPrice($capital) }}"
            placeholder="Rp 0" aria-label="Harga modal" @required(! $fromStock) @readonly($fromStock)
            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </td>

    <td>
        <input type="text" inputmode="numeric" class="item-selling text-right" value="{{ $formatPrice($selling) }}"
            placeholder="Rp 0" aria-label="Harga jual" @required(! $fromStock) @readonly($fromStock)
            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <p class="item-price-warning mt-1.5 hidden text-sm text-red-600">Harus lebih besar dari harga modal.</p>
    </td>

    <td class="item-total whitespace-nowrap !ps-4 !pt-4 text-end text-sm font-medium tabular-nums text-gray-950">
        Rp {{ number_format($selling * max(1, (int) ($row['quantity'] ?? 1)), 0, ',', '.') }}
    </td>

    <td class="text-center">
        <button type="button" class="remove-row-btn" title="Hapus item">
            <x-fi.icon name="trash" class="h-5 w-5" />
            <span class="sr-only">Hapus item</span>
        </button>
    </td>
</tr>
