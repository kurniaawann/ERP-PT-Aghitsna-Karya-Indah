{{-- =====================================================================
     Halaman: Form Tambah / Edit Rekap Penjualan (Finance)
     Tujuan: Pengganti modal tambah/edit Rekap Penjualan — informasi laporan,
             ringkasan total, tabel item (dari stok / manual), dan (edit) bukti
             pembayaran dalam satu halaman penuh.

     Data dari RecapSalesController@create / @edit:
     - $sale  : SalesRecap (edit, + paymentProofs) | null (tambah).
                Rekap yang sudah lunas tidak bisa dibuka di sini (controller
                mengalihkan ke daftar), sama seperti update().
     - $items : master barang (pilihan "Dari stok")

     Submit ke route('recap-sales.store') (POST) / route('recap-sales.update')
     (PUT). Baris item diserialisasi form.js: tambah → JSON di #items-json,
     edit → input items[i][field]. Gagal → kembali ke halaman ini dengan old()
     (baris item ikut dipulihkan). Berhasil → daftar Rekap Penjualan (Super
     Admin: halaman Rekap tab Penjualan).

     JS yang di-load:
     - @vite('resources/js/pages/finance/sales-recaps/form.js')
     - @vite('resources/js/pages/finance/payment-proofs/embedded.js') (edit)
     ===================================================================== --}}
@extends('layouts.app')

@php
    $isEdit = (bool) $sale;
    $indexUrl = auth()->user()->isSuperAdmin() ? route('rekap.index', ['tab' => 'sales']) : route('recap-sales.index');

    $decode = fn ($value) => is_string($value) ? json_decode($value, true) : $value;

    // Baris item awal: input lama (gagal simpan; JSON saat tambah, array saat edit)
    // → item tersimpan → 1 baris kosong
    $rows = session()->hasOldInput('items') ? $decode(old('items')) : null;
    if (! is_array($rows)) {
        $rows = $isEdit ? (array) $decode($sale->items) : [];
    }
    $rows = array_values(array_filter($rows, 'is_array')) ?: [[]];

    // Stok yang tersedia untuk rekap ini. Saat update, stok item lama dikembalikan
    // dulu sebelum dikurangi lagi (RecapSalesService::reconcileStock), jadi qty yang
    // sudah tercatat di rekap ini ikut dihitung tersedia.
    $reserved = [];
    if ($isEdit) {
        foreach ((array) $decode($sale->items) as $saved) {
            if (filter_var($saved['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN) && ! empty($saved['id_item'])) {
                $reserved[$saved['id_item']] = ($reserved[$saved['id_item']] ?? 0) + (int) ($saved['quantity'] ?? 0);
            }
        }
    }
@endphp

@section('title', 'PT Aghitsna Karya Indah - ' . ($isEdit ? 'Edit' : 'Tambah') . ' Rekap Penjualan')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header :heading="$isEdit ? 'Edit Rekap Penjualan' : 'Tambah Rekap Penjualan'"
            :breadcrumbs="['Finance' => null, 'Rekap Penjualan' => $indexUrl, ($isEdit ? 'Edit' : 'Tambah') => null]" />

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        <form id="sales-recap-form" method="POST" data-mode="{{ $isEdit ? 'edit' : 'create' }}"
            action="{{ $isEdit ? route('recap-sales.update', $sale->id_sales_recap) : route('recap-sales.store') }}"
            class="flex flex-col gap-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- Desktop: Informasi + Ringkasan, Item (lebar penuh). Mobile: Informasi → Item → Ringkasan. --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- SECTION: Informasi Laporan --}}
                <x-fi.section heading="Informasi Laporan" class="lg:col-span-2">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">

                        {{-- Field: ID Laporan (edit; dibuat otomatis saat tambah, tidak dapat diubah) --}}
                        @if ($isEdit)
                            <x-fi.field label="ID Laporan" hint="ID laporan tidak dapat diubah.">
                                <x-fi.input :value="$sale->id_sales_recap" prefix-icon="lock-closed" aria-label="ID Laporan" readonly />
                            </x-fi.field>
                        @endif

                        {{-- Field: Tanggal --}}
                        <x-fi.field label="Tanggal" for="sale-date" required>
                            <x-fi.input type="date" id="sale-date" name="date" required
                                :value="old('date', $sale?->date?->format('Y-m-d'))"
                                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                        </x-fi.field>

                        {{-- Field: Nama Proyek --}}
                        <x-fi.field label="Nama Proyek" for="name-proyek" required :class="$isEdit ? 'sm:col-span-2' : ''">
                            <x-fi.input id="name-proyek" name="name_proyek" :value="old('name_proyek', $sale?->name_proyek)"
                                placeholder="Contoh: PROYEK KAHFI" maxlength="255" required
                                oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                        </x-fi.field>
                    </div>
                </x-fi.section>

                {{-- SECTION: Ringkasan (dihitung ulang form.js setiap item berubah) --}}
                <x-fi.section heading="Ringkasan" class="order-1 self-start lg:order-none">
                    <dl class="grid gap-y-3 text-sm">
                        @if ($isEdit)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Status</dt>
                                <dd><x-fi.badge color="warning">{{ $sale->status }}</x-fi.badge></dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Jumlah item</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="rows">0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Total HPP (modal)</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="capital">Rp 0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Total penjualan</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="selling">Rp 0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-3">
                            <dt class="font-semibold text-gray-950">Profit</dt>
                            <dd class="text-base font-semibold tabular-nums text-emerald-600" data-summary="profit">Rp 0</dd>
                        </div>
                    </dl>
                </x-fi.section>

                {{-- SECTION: Item Barang (repeater tabel) --}}
                <x-fi.section heading="Item Barang"
                    description="Centang “Dari stok” untuk memotong stok barang; harga mengikuti Data Barang."
                    flush class="lg:col-span-3">
                    <x-slot:actions>
                        <x-fi.button color="gray" size="sm" icon="plus" data-add-item-row>Tambah Item</x-fi.button>
                    </x-slot:actions>

                    {{-- relative: elemen absolute di tabel (teks sr-only) tetap terpotong di sini, tidak melebarkan halaman di HP --}}
                    <div class="relative overflow-x-auto">
                        <table class="w-full table-auto divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="min-w-[16rem] py-2 pe-2 ps-4 text-start text-sm font-semibold text-gray-950">
                                        Barang<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                    </th>
                                    <th class="w-24 min-w-[5.5rem] px-2 py-2 text-center text-sm font-semibold text-gray-950">
                                        Qty<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                    </th>
                                    <th class="min-w-[9rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">
                                        HPP (Harga Modal)<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                    </th>
                                    <th class="min-w-[9rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">
                                        Harga Jual<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                    </th>
                                    <th class="min-w-[8rem] py-2 pe-2 ps-4 text-end text-sm font-semibold text-gray-950">Jumlah</th>
                                    <th class="w-12 py-2 pe-4 ps-2"><span class="sr-only">Hapus</span></th>
                                </tr>
                            </thead>

                            <tbody id="item-rows" class="fi-repeater fi-repeater-top divide-y divide-gray-200 bg-white">
                                @foreach ($rows as $row)
                                    @include('components.finance.sales-recaps.item-row', ['row' => $row])
                                @endforeach
                            </tbody>

                            <tfoot class="border-t border-gray-200 bg-gray-50">
                                <tr>
                                    <td class="py-3 pe-2 ps-4 text-sm font-semibold text-gray-950">Total</td>
                                    <td class="px-2 py-3 text-center text-sm font-semibold tabular-nums text-gray-950" data-summary="qty">0</td>
                                    <td class="whitespace-nowrap px-2 py-3 text-end text-sm font-semibold tabular-nums text-gray-950" data-summary="capital">Rp 0</td>
                                    <td></td>
                                    <td class="whitespace-nowrap py-3 pe-2 ps-4 text-end text-sm font-semibold tabular-nums text-gray-950" data-summary="selling">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-fi.section>
            </div>

            {{-- SECTION: Bukti Pembayaran (edit; upload/hapus lewat modal di luar form, langsung tersimpan) --}}
            @if ($isEdit)
                <x-fi.section heading="Bukti Pembayaran"
                    description="Upload/hapus bukti langsung tersimpan dan halaman dimuat ulang — simpan perubahan form terlebih dahulu.">
                    <x-finance.payment-proofs.manager :proofs="$sale->paymentProofs" invoiceType="rekap_penjualan"
                        :invoiceKey="$sale->id_sales_recap" :manualAmount="false" bare />
                </x-fi.section>
            @endif

            {{-- Hidden: items (diisi form.js saat submit; JSON saat tambah, items[i][field] saat edit) --}}
            <div id="items-payload" class="hidden">
                @unless ($isEdit)
                    <input type="hidden" name="items" id="items-json" value="[]">
                @endunless
            </div>

            {{-- SECTION: Aksi Form (kanan bawah: [Batal] [Simpan]) --}}
            <div class="flex flex-wrap items-center justify-end gap-3">
                <x-fi.button color="gray" :href="$indexUrl">Batal</x-fi.button>
                <x-fi.button type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</x-fi.button>
            </div>
        </form>
    </div>

    {{-- Template baris item baru (dipakai form.js untuk tombol "Tambah Item") --}}
    <template id="item-row-template">
        @include('components.finance.sales-recaps.item-row', ['row' => []])
    </template>

    {{-- Modal Upload & Hapus Bukti Pembayaran (di luar form agar tidak bersarang) --}}
    @if ($isEdit)
        @include('components.finance.payment-proofs.upload-modal')
        @include('components.finance.payment-proofs.delete-modal')
    @endif

    {{-- SECTION: Data barang untuk pilihan "Dari stok" (dibaca form.js).
         quantity = stok tersedia untuk rekap ini (stok master + qty yang sudah
         tercatat di rekap ini saat edit). --}}
    <script type="application/json" id="itemsDataJson">{!! json_encode($items->map(fn ($item) => [
        'id_item' => $item->id_item,
        'name_item' => $item->name_item,
        'capital_price' => (int) $item->capital_price,
        'selling_price' => (int) $item->selling_price,
        'quantity' => (int) $item->quantity + ($reserved[$item->id_item] ?? 0),
    ])->values(), JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite(array_values(array_filter([
            'resources/js/pages/finance/sales-recaps/form.js',
            $isEdit ? 'resources/js/pages/finance/payment-proofs/embedded.js' : null,
        ])))
    @endpush
@endsection
