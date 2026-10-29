{{-- =====================================================================
     Satu kartu Faktur Pembelian pada halaman Tambah Faktur Pembelian
     (repeater kartu, bisa banyak faktur sekaligus + tutup/buka kartu).

     Variabel:
     - $index : indeks array invoices[..]. Pada <template id="purchaseInvoiceRowTemplate">
                diisi '__INDEX__' yang diganti JS dengan indeks unik.
     - $old   : array input lama satu faktur (old('invoices')[$index]) saat
                penyimpanan gagal; null untuk kartu baru.
     Pesan error validasi server tampil di bawah field masing-masing
     (kunci invoices.{index}.{field}).

     Hook JS (resources/js/pages/finance/purchase-invoices/create.js):
     - .purchase-invoice-card / .card-header / .card-body / .card-chevron
     - .card-number (nomor faktur) / .card-summary (ringkasan saat ditutup)
     - .purchase-invoice-remove (hapus kartu)
     - .pi-copy-wrapper / .pi-copy-from-first (salin dari faktur ke-1)
     - .pi-date / .pi-material / .pi-npwp / .pi-tax-code / .pi-item
     - .pi-selling-price / .pi-ppn-percentage / .pi-ppn-tax / .pi-notes
     ===================================================================== --}}
@php
    $old = is_array($old ?? null) ? $old : [];
    $field = fn (string $key) => "invoices.{$index}.{$key}";

    $sellingDigits = preg_replace('/[^0-9]/', '', (string) ($old['selling_price'] ?? ''));
    $sellingValue = $sellingDigits === '' ? '' : number_format((int) $sellingDigits, 0, ',', '.');
@endphp

<div class="purchase-invoice-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/10">

    {{-- Header kartu (klik untuk tutup/buka) --}}
    <div class="card-header flex min-h-[3rem] cursor-pointer select-none items-center justify-between gap-3 rounded-t-xl border-b border-gray-100 bg-gray-50 px-4 py-2"
        onclick="toggleInvoiceCard(this)" title="Klik untuk tutup/buka">
        <div class="flex min-w-0 items-center gap-2">
            <x-fi.icon name="chevron-down" class="card-chevron h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" />
            <span class="shrink-0 text-sm font-medium text-gray-950">Faktur <span class="card-number">1</span></span>
            <span class="card-summary truncate text-sm text-gray-500"></span>
        </div>

        <button type="button" onclick="removeInvoiceCard(event, this)" title="Hapus faktur ini"
            class="purchase-invoice-remove h-8 w-8 shrink-0 rounded-lg p-1.5 text-gray-400 transition duration-75 hover:bg-red-50 hover:text-red-600">
            <x-fi.icon name="trash" class="h-5 w-5" />
            <span class="sr-only">Hapus faktur ini</span>
        </button>
    </div>

    {{-- Body kartu --}}
    <div class="card-body grid gap-y-5 p-4">
        {{-- Salin dari faktur ke-1 (kartu ke-2 dst; diatur JS) --}}
        <label class="pi-copy-wrapper hidden cursor-pointer items-start gap-3 rounded-lg bg-blue-50 p-3 ring-1 ring-primary/20">
            <input type="checkbox" class="pi-copy-from-first fi-checkbox mt-0.5" onchange="handleCopyFromFirst(this)">
            <span class="grid">
                <span class="text-sm font-medium text-gray-950">Sama dengan Faktur ke-1</span>
                <span class="text-sm text-gray-500">Salin semua kolom dari faktur pertama.</span>
            </span>
        </label>

        <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Tanggal --}}
            <x-fi.field label="Tanggal" required>
                <x-fi.input type="date" name="invoices[{{ $index }}][date]" class="pi-date" aria-label="Tanggal"
                    :value="$old['date'] ?? date('Y-m-d')" required
                    oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                @error($field('date')) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </x-fi.field>

            {{-- Nama Material --}}
            <x-fi.field label="Nama Material" required class="lg:col-span-2">
                <x-fi.input name="invoices[{{ $index }}][material_name]" class="pi-material" aria-label="Nama Material"
                    :value="$old['material_name'] ?? ''" placeholder="Contoh: PT. MANGGALA DIPO PRATAMA" maxlength="255" required
                    oninvalid="this.setCustomValidity('Nama material tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                @error($field('material_name')) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </x-fi.field>

            {{-- NPWP --}}
            <x-fi.field label="NPWP" required>
                <x-fi.input name="invoices[{{ $index }}][npwp]" class="pi-npwp" aria-label="NPWP"
                    :value="$old['npwp'] ?? ''" placeholder="Contoh: 80.948.827.3-047.000" maxlength="50" required
                    oninvalid="this.setCustomValidity('NPWP tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                @error($field('npwp')) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </x-fi.field>

            {{-- Kode Nomor Seri Pajak --}}
            <x-fi.field label="Kode Nomor Seri Pajak" required>
                <x-fi.input name="invoices[{{ $index }}][tax_number_code]" class="pi-tax-code" aria-label="Kode Nomor Seri Pajak"
                    :value="$old['tax_number_code'] ?? ''" placeholder="Contoh: 011.011-23.82258306" maxlength="50" required
                    oninvalid="this.setCustomValidity('Kode nomor seri pajak tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                @error($field('tax_number_code')) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </x-fi.field>

            {{-- Nama Barang --}}
            <x-fi.field label="Nama Barang" required class="sm:col-span-2 lg:col-span-1">
                <x-fi.input name="invoices[{{ $index }}][item_name]" class="pi-item" aria-label="Nama Barang"
                    :value="$old['item_name'] ?? ''" placeholder="Contoh: SEMEN 40 KG" maxlength="255" required
                    oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                @error($field('item_name')) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </x-fi.field>

            {{-- Harga Jual --}}
            <x-fi.field label="Harga Jual" required>
                <x-fi.input name="invoices[{{ $index }}][selling_price]" class="pi-selling-price" aria-label="Harga Jual"
                    prefix="Rp" :value="$sellingValue" placeholder="0" inputmode="numeric" required
                    oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                @error($field('selling_price')) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </x-fi.field>

            {{-- Persentase PPN --}}
            <x-fi.field label="Persentase PPN" required hint="Default 11%. Boleh pakai koma, contoh 11,5.">
                <x-fi.input name="invoices[{{ $index }}][ppn_percentage]" class="pi-ppn-percentage" aria-label="Persentase PPN"
                    suffix="%" :value="$old['ppn_percentage'] ?? '11'" placeholder="11" inputmode="decimal" required
                    oninvalid="this.setCustomValidity('Persentase PPN tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                @error($field('ppn_percentage')) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </x-fi.field>

            {{-- PPN Pengenaan Pajak (dihitung otomatis) --}}
            <x-fi.field label="PPN Pengenaan Pajak" hint="Otomatis: harga jual × persentase PPN.">
                <x-fi.input class="pi-ppn-tax" aria-label="PPN Pengenaan Pajak" prefix="Rp" value="0" readonly />
            </x-fi.field>

            {{-- Keterangan --}}
            <x-fi.field label="Keterangan" class="sm:col-span-2 lg:col-span-3">
                <x-fi.textarea name="invoices[{{ $index }}][notes]" class="pi-notes" rows="2"
                    placeholder="Keterangan tambahan (opsional)">{{ $old['notes'] ?? '' }}</x-fi.textarea>
            </x-fi.field>
        </div>
    </div>
</div>
