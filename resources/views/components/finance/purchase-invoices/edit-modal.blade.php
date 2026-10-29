{{-- Modal Edit Faktur Pembelian (gaya Filament), satu per baris.
     Hook JS (purchase-invoices/index.js): [id^="editModal-"] form + button[type=submit],
     #editSellingPrice-{id}, #editPpnPercentage-{id}, #editPpnTax-{id}, #editTotal-{id}.
     Penyimpanan gagal → modal ini dibuka lagi (name="_modal") berisi input
     lama + pesan error. --}}
@props(['invoice'])

@php
    $modalId = 'editModal-' . $invoice->id;

    // Input lama hanya dipakai bila error berasal dari modal ini
    $isEditError = $errors->any() && old('_modal') === $modalId;
    $value = fn (string $key, $default) => $isEditError ? old($key, $default) : $default;

    $sellingDigits = preg_replace('/[^0-9]/', '', (string) $value('selling_price', $invoice->selling_price));
    $percent = rtrim(rtrim(number_format((float) $invoice->ppn_percentage, 2, ',', '.'), '0'), ',');
@endphp

<x-fi.modal :id="$modalId" heading="Edit Faktur Pembelian" :description="'Kode seri pajak ' . $invoice->tax_number_code"
    icon="pencil-square" :action="route('purchase-invoice.update', $invoice->id)" method="PUT" submitLabel="Update"
    width="2xl">

    <input type="hidden" name="_modal" value="{{ $modalId }}">

    @if ($isEditError)
        <x-fi.validation-errors />
    @endif

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        <x-fi.field label="Tanggal" required>
            <x-fi.input type="date" name="date" aria-label="Tanggal" required
                :value="$value('date', $invoice->date?->format('Y-m-d'))"
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        <x-fi.field label="Nama Material" required>
            <x-fi.input name="material_name" aria-label="Nama Material" maxlength="255" required
                :value="$value('material_name', $invoice->material_name)"
                oninvalid="this.setCustomValidity('Nama material tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        <x-fi.field label="NPWP" required>
            <x-fi.input name="npwp" aria-label="NPWP" maxlength="50" required
                :value="$value('npwp', $invoice->npwp)"
                oninvalid="this.setCustomValidity('NPWP tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        <x-fi.field label="Kode Nomor Seri Pajak" required>
            <x-fi.input name="tax_number_code" aria-label="Kode Nomor Seri Pajak" maxlength="50" required
                :value="$value('tax_number_code', $invoice->tax_number_code)"
                oninvalid="this.setCustomValidity('Kode nomor seri pajak tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        <x-fi.field label="Nama Barang" required class="sm:col-span-2">
            <x-fi.input name="item_name" aria-label="Nama Barang" maxlength="255" required
                :value="$value('item_name', $invoice->item_name)"
                oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        <x-fi.field label="Harga Jual" for="editSellingPrice-{{ $invoice->id }}" required>
            <x-fi.input id="editSellingPrice-{{ $invoice->id }}" name="selling_price" prefix="Rp" inputmode="numeric" required
                :value="$sellingDigits === '' ? '' : number_format((int) $sellingDigits, 0, ',', '.')" placeholder="0"
                oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        <x-fi.field label="Persentase PPN" for="editPpnPercentage-{{ $invoice->id }}" required hint="Boleh pakai koma, contoh 11,5.">
            <x-fi.input id="editPpnPercentage-{{ $invoice->id }}" name="ppn_percentage" suffix="%" inputmode="decimal" required
                :value="$value('ppn_percentage', $percent)" placeholder="11"
                oninvalid="this.setCustomValidity('Persentase PPN tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        {{-- PPN & Total dihitung otomatis (server menghitung ulang PPN saat simpan) --}}
        <x-fi.field label="PPN Pengenaan Pajak" for="editPpnTax-{{ $invoice->id }}">
            <x-fi.input id="editPpnTax-{{ $invoice->id }}" prefix="Rp" readonly
                :value="number_format($invoice->ppn_tax, 0, ',', '.')" />
        </x-fi.field>

        <x-fi.field label="Total" for="editTotal-{{ $invoice->id }}">
            <x-fi.input id="editTotal-{{ $invoice->id }}" prefix="Rp" readonly
                :value="number_format($invoice->total, 0, ',', '.')" />
        </x-fi.field>

        <x-fi.field label="Keterangan" class="sm:col-span-2">
            <x-fi.textarea name="notes" rows="2" placeholder="Keterangan tambahan (opsional)">{{ $value('notes', $invoice->notes) }}</x-fi.textarea>
        </x-fi.field>
    </div>
</x-fi.modal>
