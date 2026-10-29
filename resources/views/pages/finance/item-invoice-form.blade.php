{{-- =====================================================================
     Halaman: Form Tambah / Edit Invoice Barang (Finance)
     Tujuan: Pengganti modal tambah/edit Invoice Barang — informasi invoice,
             ringkasan total, tabel item (dari stok / manual), rekening
             pembayaran, tanda tangan, dan (edit) bukti pembayaran dalam satu
             halaman penuh.

     Data dari ItemInvoiceController@create / @edit:
     - $invoice           : InvoiceBarang (edit, + salesRecap & paymentProofs) | null (tambah)
     - $isEditable        : false bila invoice sudah Lunas (edit) → form dikunci,
                            bukti pembayaran tetap bisa dikelola
     - $nextInvoiceNumber : perkiraan no. invoice berikutnya (tambah)
     - $items             : master barang (dropdown "Dari stok")
     - $paymentAccounts   : rekening pembayaran aktif
     - $executives / $divisions : pilihan penandatangan & divisi

     Submit ke route('item-invoice.store') (POST) / route('item-invoice.update')
     (PUT). Baris item diserialisasi form.js: tambah → JSON di #items-json,
     edit → input items[i][field]. Gagal → kembali ke halaman ini dengan old()
     (baris item ikut dipulihkan).

     JS yang di-load:
     - @vite('resources/js/pages/finance/item-invoices/form.js')
     - @vite('resources/js/pages/finance/payment-proofs/embedded.js') (edit)
     ===================================================================== --}}
@extends('layouts.app')

@php
    $isEdit = (bool) $invoice;
    $isEditable = $isEditable ?? true;

    $decode = fn ($value) => is_string($value) ? json_decode($value, true) : $value;

    // Baris item awal: input lama (gagal simpan; JSON saat tambah, array saat edit)
    // → item tersimpan → 1 baris kosong
    $rows = session()->hasOldInput('items') ? $decode(old('items')) : null;
    if (! is_array($rows)) {
        $rows = $isEdit ? (array) $decode($invoice->items) : [];
    }
    $rows = array_values(array_filter($rows, 'is_array')) ?: [[]];

    // Stok yang tersedia untuk invoice ini. Saat update, stok item invoice lama
    // dikembalikan dulu sebelum dikurangi lagi (ItemInvoiceService), jadi qty
    // yang sudah tercatat di invoice ini ikut dihitung tersedia.
    $reserved = [];
    if ($isEdit) {
        foreach ((array) $decode($invoice->salesRecap?->items ?? $invoice->items) as $saved) {
            if (! empty($saved['from_stock']) && ! empty($saved['id_item'])) {
                $reserved[$saved['id_item']] = ($reserved[$saved['id_item']] ?? 0) + (int) ($saved['quantity'] ?? 0);
            }
        }
    }

    $selectedAccounts = array_map('intval', (array) (session()->hasOldInput()
        ? old('selected_payment_accounts', [])
        : ($decode($invoice?->selected_payment_accounts) ?? [])));

    $statusLunas = $invoice?->salesRecap?->isLunas() ?? false;
@endphp

@section('title', 'PT Aghitsna Karya Indah - ' . ($isEdit ? 'Edit' : 'Tambah') . ' Invoice Barang')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header :heading="$isEdit ? 'Edit Invoice Barang' : 'Tambah Invoice Barang'"
            :breadcrumbs="['Finance' => null, 'Invoice Barang' => route('item-invoice.index'), ($isEdit ? 'Edit' : 'Tambah') => null]">
            @if ($isEdit)
                <x-slot:actions>
                    <x-fi.button color="gray" icon="printer" :href="route('item-invoice.print.pdf', $invoice->invoice_number)">
                        Cetak PDF
                    </x-fi.button>
                </x-slot:actions>
            @endif
        </x-fi.page-header>

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        {{-- SECTION: Info invoice lunas (form dikunci, bukti pembayaran tetap bisa dikelola) --}}
        @unless ($isEditable)
            <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-600/20">
                <x-fi.icon name="lock-closed" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                <div>
                    <p class="font-semibold">Invoice sudah lunas</p>
                    <p class="mt-0.5">Data invoice tidak dapat diubah lagi. Bukti pembayaran tetap dapat dikelola di bawah.</p>
                </div>
            </div>
        @endunless

        <form id="item-invoice-form" method="POST" data-mode="{{ $isEdit ? 'edit' : 'create' }}"
            action="{{ $isEdit ? route('item-invoice.update', $invoice->invoice_number) : route('item-invoice.store') }}"
            class="flex flex-col gap-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- Seluruh isi form dikunci (disabled) bila invoice sudah lunas --}}
            <fieldset @disabled(! $isEditable) class="min-w-0">
                {{-- Desktop: Informasi + Ringkasan, Item (lebar penuh), Rekening + Tanda Tangan.
                     Mobile: Informasi → Item → Ringkasan → Rekening → Tanda Tangan. --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    {{-- SECTION: Informasi Invoice --}}
                    <x-fi.section heading="Informasi Invoice" class="lg:col-span-2">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">

                            {{-- Field: No. Invoice (dibuat otomatis, tidak dapat diubah) --}}
                            <x-fi.field label="No. Invoice"
                                :hint="$isEdit ? 'No. invoice tidak dapat diubah.' : 'Dibuat otomatis saat invoice disimpan.'">
                                <x-fi.input :value="$isEdit ? $invoice->invoice_number : $nextInvoiceNumber" prefix-icon="lock-closed"
                                    aria-label="No. Invoice" readonly />
                            </x-fi.field>

                            {{-- Field: Tanggal Invoice --}}
                            <x-fi.field label="Tanggal Invoice" for="invoice-date" required>
                                <x-fi.input type="date" id="invoice-date" name="invoice_date" required
                                    :value="old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? date('Y-m-d'))"
                                    oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                            </x-fi.field>

                            {{-- Field: Kepada --}}
                            <x-fi.field label="Kepada" for="recipient" required>
                                <x-fi.input id="recipient" name="recipient" :value="old('recipient', $invoice?->recipient)"
                                    placeholder="Nama penerima invoice" maxlength="255" required
                                    oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                            </x-fi.field>

                            {{-- Field: Hal --}}
                            <x-fi.field label="Hal" for="regarding" required>
                                <x-fi.input id="regarding" name="regarding" :value="old('regarding', $invoice?->regarding)"
                                    placeholder="Contoh: Penagihan Pembayaran" maxlength="255" required
                                    oninvalid="this.setCustomValidity('Hal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                            </x-fi.field>

                            {{-- Field: Nama Proyek --}}
                            <x-fi.field label="Nama Proyek" for="proyek">
                                <x-fi.input id="proyek" name="proyek" :value="old('proyek', $invoice?->proyek)"
                                    placeholder="Contoh: Rumah Kost" maxlength="255" />
                            </x-fi.field>

                            {{-- Field: Deskripsi Proyek (tampil sebagai keterangan di daftar invoice) --}}
                            <x-fi.field label="Deskripsi Proyek" for="project-description" class="sm:col-span-2">
                                <x-fi.textarea id="project-description" name="project_description" rows="2" maxlength="255"
                                    placeholder="Contoh: Proyek Karbela 3 / Pak Sis">{{ old('project_description', $invoice?->project_description) }}</x-fi.textarea>
                            </x-fi.field>
                        </div>
                    </x-fi.section>

                    {{-- SECTION: Ringkasan (dihitung ulang form.js setiap item berubah) --}}
                    <x-fi.section heading="Ringkasan" class="order-1 self-start lg:order-none">
                        <dl class="grid gap-y-3 text-sm">
                            @if ($isEdit)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-gray-500">Status</dt>
                                    <dd>
                                        <x-fi.badge :color="$statusLunas ? 'success' : 'warning'">{{ $invoice->status_label }}</x-fi.badge>
                                    </dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Jumlah item</dt>
                                <dd class="font-medium tabular-nums text-gray-950" data-summary="rows">0</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Total modal</dt>
                                <dd class="font-medium tabular-nums text-gray-950" data-summary="capital">Rp 0</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Estimasi profit</dt>
                                <dd class="font-medium tabular-nums text-emerald-600" data-summary="profit">Rp 0</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-3">
                                <dt class="font-semibold text-gray-950">Total invoice</dt>
                                <dd class="text-base font-semibold tabular-nums text-gray-950" data-summary="selling">Rp 0</dd>
                            </div>
                            @if ($isEdit)
                                {{-- Pembayaran dari bukti yang tersimpan (nilai saat ini, belum termasuk perubahan form) --}}
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-gray-500">Terbayar</dt>
                                    <dd class="font-medium tabular-nums text-gray-950">
                                        Rp {{ number_format($invoice->getTotalPaidAmount(), 0, ',', '.') }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-gray-500">Sisa tagihan</dt>
                                    <dd class="font-medium tabular-nums text-gray-950">
                                        Rp {{ number_format($invoice->getRemainingAmount(), 0, ',', '.') }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </x-fi.section>

                    {{-- SECTION: Item Invoice (repeater tabel) --}}
                    <x-fi.section heading="Item Invoice"
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
                                        <th class="w-28 min-w-[6rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Satuan</th>
                                        <th class="min-w-[9rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">
                                            Harga Modal<sup class="ms-0.5 font-medium text-red-600">*</sup>
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
                                        @include('components.finance.item-invoices.item-row', ['row' => $row])
                                    @endforeach
                                </tbody>

                                <tfoot class="border-t border-gray-200 bg-gray-50">
                                    <tr>
                                        <td class="py-3 pe-2 ps-4 text-sm font-semibold text-gray-950">Total</td>
                                        <td class="px-2 py-3 text-center text-sm font-semibold tabular-nums text-gray-950" data-summary="qty">0</td>
                                        <td colspan="3"></td>
                                        <td class="whitespace-nowrap py-3 pe-2 ps-4 text-end text-sm font-semibold tabular-nums text-gray-950" data-summary="selling">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </x-fi.section>

                    {{-- SECTION: Rekening Pembayaran (minimal 1 dipilih) --}}
                    <x-fi.section heading="Rekening Pembayaran" description="Pilih minimal 1 rekening yang dicantumkan di invoice."
                        class="order-2 lg:order-none lg:col-span-2">
                        <x-slot:actions>
                            <x-finance.quick-add-payment-account mode="checkbox" checkbox-container="#payment-accounts-list"
                                id-suffix="item-invoice" />
                        </x-slot:actions>

                        {{-- Kartu rekening: nama bank (.font-semibold) & info (.text-sm) dipakai
                             shared/quick-add-payment-account.js saat meng-clone kartu rekening baru --}}
                        <div id="payment-accounts-list" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @forelse ($paymentAccounts ?? [] as $account)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg p-3 ring-1 ring-gray-950/10 transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50 has-[:checked]:ring-primary">
                                    <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                                        class="payment-account-checkbox fi-checkbox mt-0.5" @checked(in_array((int) $account->id, $selectedAccounts, true))>
                                    <span class="grid min-w-0">
                                        <span class="font-semibold text-gray-950">{{ $account->bank_name }}</span>
                                        <span class="text-sm text-gray-500">No: {{ $account->account_number }} a/n {{ $account->account_holder }}</span>
                                    </span>
                                </label>
                            @empty
                                <div data-payment-accounts-empty
                                    class="flex items-start gap-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-600/20 sm:col-span-2">
                                    <x-fi.icon name="exclamation-circle" class="h-5 w-5 shrink-0 text-amber-600" />
                                    <span>
                                        Belum ada rekening pembayaran. Klik “Rekening Baru” atau kelola di
                                        <a href="{{ route('payment-accounts.index') }}" class="font-semibold underline">Rekening Pembayaran</a>.
                                    </span>
                                </div>
                            @endforelse
                        </div>

                        <p id="payment-account-error" class="mt-3 hidden text-sm text-red-600">
                            <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                            Minimal 1 rekening harus dipilih.
                        </p>
                    </x-fi.section>

                    {{-- SECTION: Tanda Tangan (opsional) --}}
                    <x-fi.section heading="Tanda Tangan" description="Opsional." class="order-3 self-start lg:order-none">
                        <div class="grid gap-y-5">
                            <x-fi.field label="Penandatangan" for="signed-by-id">
                                <x-fi.select id="signed-by-id" name="signed_by_id" prefix-icon="user" placeholder="Tidak ada" data-fi-select>
                                    @foreach ($executives as $executive)
                                        <option value="{{ $executive->id }}" @selected((int) old('signed_by_id', $invoice?->signed_by_id) === (int) $executive->id)>
                                            {{ $executive->name }} ({{ $executive->position }})
                                        </option>
                                    @endforeach
                                </x-fi.select>
                            </x-fi.field>

                            <x-fi.field label="Divisi" for="division-id">
                                <x-fi.select id="division-id" name="division_id" prefix-icon="building-office-2" placeholder="Tidak ada" data-fi-select>
                                    @foreach ($divisions as $division)
                                        <option value="{{ $division->id }}" @selected((int) old('division_id', $invoice?->division_id) === (int) $division->id)>
                                            {{ $division->name }}
                                        </option>
                                    @endforeach
                                </x-fi.select>
                            </x-fi.field>
                        </div>
                    </x-fi.section>
                </div>
            </fieldset>

            {{-- SECTION: Bukti Pembayaran (edit; upload/hapus lewat modal di luar form, langsung tersimpan) --}}
            @if ($isEdit)
                <x-fi.section heading="Bukti Pembayaran"
                    description="Upload/hapus bukti langsung tersimpan dan halaman dimuat ulang — simpan perubahan form terlebih dahulu.">
                    <x-finance.payment-proofs.manager :proofs="$invoice->paymentProofs" invoiceType="barang"
                        :invoiceKey="$invoice->invoice_number" :manualAmount="false" bare />
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
                @if ($isEditable)
                    <x-fi.button color="gray" :href="route('item-invoice.index')">Batal</x-fi.button>
                    <x-fi.button type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</x-fi.button>
                @else
                    <x-fi.button color="gray" icon="chevron-left" :href="route('item-invoice.index')">Kembali</x-fi.button>
                @endif
            </div>
        </form>
    </div>

    {{-- Template baris item baru (dipakai form.js untuk tombol "Tambah Item") --}}
    <template id="item-row-template">
        @include('components.finance.item-invoices.item-row', ['row' => []])
    </template>

    {{-- Modal Upload & Hapus Bukti Pembayaran (di luar form agar tidak bersarang) --}}
    @if ($isEdit)
        @include('components.finance.payment-proofs.upload-modal')
        @include('components.finance.payment-proofs.delete-modal')
    @endif

    {{-- SECTION: Data barang untuk pilihan "Dari stok" (dibaca form.js).
         quantity = stok tersedia untuk invoice ini (stok master + qty yang sudah
         tercatat di invoice ini saat edit). --}}
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
            'resources/js/pages/finance/item-invoices/form.js',
            $isEdit ? 'resources/js/pages/finance/payment-proofs/embedded.js' : null,
        ])))
    @endpush
@endsection
