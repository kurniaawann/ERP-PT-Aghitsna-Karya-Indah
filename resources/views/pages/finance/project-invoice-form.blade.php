{{-- =====================================================================
     Halaman: Form Tambah / Edit Invoice Proyek (Finance)
     Tujuan: Pengganti modal tambah/edit Invoice Proyek — informasi invoice,
             tautan Rekap Proyek, item, diskon & DP, PPN, rekening
             pembayaran, tanda tangan, ringkasan total, dan (edit) bukti
             pembayaran dalam satu halaman penuh.

     Isi menyesuaikan role (sama seperti modal lama):
     - Admin      : judul "Invoice"; No Invoice diketik nomor urutnya
                    (admin-number-field); field Lokasi; item format
                    deskripsi / harga / persentase (opsional); tanpa Diskon & DP.
     - Superadmin : judul "Invoice Proyek"; No Invoice digenerate server;
                    field Nama Proyek; item format keterangan / volume &
                    satuan (opsional) / harga; Diskon & DP.
     - Semua      : Rekap Proyek (opsional), PPN, tanda tangan, rekening.

     Data dari ProyekInvoiceController@create / @edit:
     - $invoice           : InvoiceProyek (edit, + paymentProofs) | null (tambah)
     - $nextInvoiceNumber : perkiraan no. invoice berikutnya (tambah, non-admin)
     - $paymentAccounts, $executives, $divisions, $projectRecaps
     - $takenAdminNumbers : nomor admin yang sudah dipakai (cek nomor dobel live)

     Submit ke route('proyek-invoice.store') (POST) / route('proyek-invoice.update')
     (PUT). Baris item diserialisasi form.js: tambah → JSON di #items-json,
     edit → input items[i][field]. Gagal → kembali ke halaman ini dengan old()
     (baris item ikut dipulihkan).

     JS yang di-load:
     - @vite('resources/js/pages/finance/project-invoices/form.js')
     - @vite('resources/js/pages/finance/payment-proofs/embedded.js') (edit)
     ===================================================================== --}}
@extends('layouts.app')

@php
    $isEdit = (bool) $invoice;
    $isAdmin = auth()->user()->isAdmin();
    $isSuperadmin = auth()->user()->role === 'superadmin';
    $label = $isAdmin ? 'Invoice' : 'Invoice Proyek';

    // Admin + nomor format baru ({nomor}/AKI/{bulan}/{yyyy}): nomor urut bisa diubah.
    // Nomor format lama & nomor superadmin: read-only.
    $editableNumber = $isAdmin && (! $isEdit || \App\Models\Finance\InvoiceProyek::parseAdminInvoiceNumber($invoice->invoice_number));

    $decode = fn ($value) => is_string($value) ? json_decode($value, true) : $value;

    // Baris item awal: input lama (gagal simpan; JSON saat tambah, array saat edit)
    // → item tersimpan → 1 baris kosong
    $rows = session()->hasOldInput('items') ? $decode(old('items')) : null;
    if (! is_array($rows)) {
        $rows = $isEdit ? (array) $decode($invoice->items) : [];
    }
    $rows = array_values(array_filter($rows, 'is_array')) ?: [[]];

    // Total awal item: diskon, DP & PPN hanya aktif bila sudah ada total (sama seperti form lama)
    $initialTotal = collect($rows)->sum(fn ($row) => $row === [] ? 0 : \App\Models\Finance\InvoiceProyek::itemAmount(array_merge($row, [
        'harga' => \App\Services\InputNormalizer::normalizeCurrency($row['harga'] ?? 0),
    ])));
    $hasTotal = $initialTotal > 0;

    $selectedAccounts = array_map('intval', (array) (session()->hasOldInput()
        ? old('selected_payment_accounts', [])
        : ($decode($invoice?->selected_payment_accounts) ?? [])));

    $typeOptions = fn (string $none) => ['' => $none, 'percentage' => 'Persentase (%)', 'amount' => 'Nominal (Rp)'];
@endphp

@section('title', 'PT Aghitsna Karya Indah - ' . ($isEdit ? 'Edit ' : 'Tambah ') . $label)

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header :heading="($isEdit ? 'Edit ' : 'Tambah ') . $label"
            :breadcrumbs="['Finance' => null, $label => route('proyek-invoice.index'), ($isEdit ? 'Edit' : 'Tambah') => null]">
            @if ($isEdit)
                <x-slot:actions>
                    <x-fi.button color="gray" icon="printer" :href="route('proyek-invoice.print.pdf', $invoice->invoice_number)">
                        Cetak PDF
                    </x-fi.button>
                </x-slot:actions>
            @endif
        </x-fi.page-header>

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        <form id="proyek-invoice-form" method="POST" data-mode="{{ $isEdit ? 'edit' : 'create' }}"
            data-item-format="{{ $isAdmin ? 'admin' : 'standard' }}"
            action="{{ $isEdit ? route('proyek-invoice.update', $invoice->invoice_number) : route('proyek-invoice.store') }}"
            class="flex flex-col gap-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- Desktop: kolom utama (informasi, rekap, item, diskon & DP, PPN, rekening) +
                 kolom samping (ringkasan menempel saat scroll, tanda tangan).
                 Mobile: kolom utama → ringkasan → tanda tangan. --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
                <div class="grid min-w-0 gap-6 lg:col-span-2">

                    {{-- SECTION: Informasi Invoice --}}
                    <x-fi.section heading="Informasi Invoice">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">

                            {{-- Field: No. Invoice --}}
                            @if ($editableNumber)
                                {{-- Admin: ketik nomor urut, sisanya disusun dari Tanggal Invoice --}}
                                <x-finance.project-invoices.admin-number-field :invoice="$invoice" class="sm:col-span-2" />
                            @else
                                <x-fi.field label="No. Invoice"
                                    :hint="$isEdit ? 'No Invoice tidak dapat diubah.' : 'Dibuat otomatis saat invoice disimpan.'">
                                    <x-fi.input :value="$isEdit ? $invoice->invoice_number : $nextInvoiceNumber" prefix-icon="lock-closed"
                                        aria-label="No. Invoice" readonly />
                                </x-fi.field>
                            @endif

                            {{-- Field: Tanggal Invoice --}}
                            <x-fi.field label="Tanggal Invoice" for="invoice-date" required>
                                <x-fi.input type="date" id="invoice-date" name="invoice_date" required
                                    :value="old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? date('Y-m-d'))"
                                    oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                            </x-fi.field>

                            {{-- Field: Kepada --}}
                            <x-fi.field label="Kepada (Nama Penerima)" for="recipient" required>
                                <x-fi.input id="recipient" name="recipient" :value="old('recipient', $invoice?->recipient)"
                                    placeholder="Nama penerima invoice" maxlength="255" required
                                    oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                            </x-fi.field>

                            {{-- Field: Hal --}}
                            <x-fi.field label="Hal / Regarding" for="regarding" required>
                                <x-fi.input id="regarding" name="regarding" :value="old('regarding', $invoice?->regarding)"
                                    placeholder="Contoh: Pengajuan Dana" maxlength="255" required
                                    oninvalid="this.setCustomValidity('Hal/Regarding tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                            </x-fi.field>

                            {{-- Field: Nama Proyek (khusus superadmin) --}}
                            @if ($isSuperadmin)
                                <x-fi.field label="Nama Proyek" for="proyek">
                                    <x-fi.input id="proyek" name="proyek" :value="old('proyek', $invoice?->proyek)"
                                        placeholder="Contoh: Rumah Kost" maxlength="255" oninput="this.setCustomValidity('')" />
                                </x-fi.field>
                            @endif

                            {{-- Field: Lokasi (khusus admin) --}}
                            @if ($isAdmin)
                                <x-fi.field label="Lokasi" for="location" hint="Opsional.">
                                    <x-fi.input id="location" name="location" :value="old('location', $invoice?->location)"
                                        placeholder="Contoh: Jl. Melati No. 1, Depok" maxlength="255" />
                                </x-fi.field>
                            @endif

                            {{-- Field: Deskripsi Proyek (tampil di kolom Proyek pada daftar invoice) --}}
                            <x-fi.field label="Deskripsi Proyek" for="project-description" class="sm:col-span-2">
                                <x-fi.textarea id="project-description" name="project_description" rows="2" maxlength="255"
                                    placeholder="Contoh: Renovasi Rumah">{{ old('project_description', $invoice?->project_description) }}</x-fi.textarea>
                            </x-fi.field>
                        </div>
                    </x-fi.section>

                    {{-- SECTION: Rekap Proyek (opsional) + ringkasan sisa tagihan --}}
                    <x-fi.section heading="Rekap Proyek" description="Opsional. Field yang masih kosong diisi otomatis dari data rekap (tetap bisa diubah).">
                        <x-finance.project-invoices.recap-picker :projectRecaps="$projectRecaps ?? collect()" :invoice="$invoice" />
                    </x-fi.section>

                    {{-- SECTION: Item Invoice (repeater tabel) --}}
                    <x-fi.section heading="Item Invoice" flush
                        :description="$isAdmin
                            ? 'Jumlah = harga × persentase ÷ 100. Persentase kosong → Jumlah = Harga.'
                            : 'Jumlah = volume × harga. Volume & satuan opsional — volume kosong → item borongan (Jumlah = Harga).'">
                        <x-slot:actions>
                            <x-fi.button color="gray" size="sm" icon="plus" data-add-item-row>Tambah Item</x-fi.button>
                        </x-slot:actions>

                        <div id="items-error" class="mx-4 mt-4 hidden rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-600/20 sm:mx-6">
                            <div class="flex items-center gap-2">
                                <x-fi.icon name="exclamation-circle" class="h-5 w-5 shrink-0 text-red-600" />
                                <span>Minimal harus ada 1 item dalam invoice dengan data lengkap</span>
                            </div>
                        </div>

                        {{-- relative: elemen absolute di tabel (teks sr-only) tetap terpotong di sini, tidak melebarkan halaman di HP --}}
                        <div class="relative overflow-x-auto">
                            <table class="w-full table-auto divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @if ($isAdmin)
                                            <th class="min-w-[16rem] py-2 pe-2 ps-4 text-start text-sm font-semibold text-gray-950">
                                                Deskripsi<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                            </th>
                                            <th class="min-w-[9rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">
                                                Harga (Rp)<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                            </th>
                                            <th class="w-28 min-w-[6rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">%</th>
                                        @else
                                            <th class="min-w-[14rem] py-2 pe-2 ps-4 text-start text-sm font-semibold text-gray-950">
                                                Keterangan<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                            </th>
                                            <th class="w-28 min-w-[6rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">Volume</th>
                                            <th class="w-28 min-w-[6rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Satuan</th>
                                            <th class="min-w-[9rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">
                                                Harga (Rp)<sup class="ms-0.5 font-medium text-red-600">*</sup>
                                            </th>
                                        @endif
                                        <th class="min-w-[8rem] py-2 pe-2 ps-4 text-end text-sm font-semibold text-gray-950">Jumlah</th>
                                        <th class="w-12 py-2 pe-4 ps-2"><span class="sr-only">Hapus</span></th>
                                    </tr>
                                </thead>

                                <tbody id="items-list" class="fi-repeater divide-y divide-gray-200 bg-white">
                                    @foreach ($rows as $row)
                                        @include('components.finance.project-invoices.item-row', ['row' => $row, 'admin' => $isAdmin])
                                    @endforeach
                                </tbody>

                                <tfoot class="border-t border-gray-200 bg-gray-50">
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 3 : 4 }}" class="py-3 pe-2 ps-4 text-sm font-semibold text-gray-950">Total</td>
                                        <td class="whitespace-nowrap py-3 pe-2 ps-4 text-end text-sm font-semibold tabular-nums text-gray-950" data-summary="subtotal">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </x-fi.section>

                    {{-- SECTION: Diskon & DP (non-admin; opsional, aktif setelah ada total item).
                         Nilai diskon, total setelah diskon, dan nilai DP tampil di Ringkasan. --}}
                    @unless ($isAdmin)
                        <x-fi.section heading="Diskon & DP" description="Opsional. Bisa diisi setelah ada item dengan total.">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                                {{-- Diskon --}}
                                <div id="discount-section" @class(['grid content-start gap-y-5 transition-opacity', 'opacity-40' => ! $hasTotal])>
                                    <h3 class="text-sm font-semibold text-gray-950">Diskon</h3>

                                    <x-fi.field label="Tipe Diskon" for="discount-type">
                                        <x-fi.select id="discount-type" name="discount_type" :options="$typeOptions('Tidak Ada Discount')"
                                            :value="old('discount_type', $invoice?->discount_type)" :disabled="! $hasTotal" data-fi-select />
                                    </x-fi.field>

                                    <x-fi.field label="Nilai Diskon" for="discount-value"
                                        hint="Tidak boleh 100% atau lebih untuk persentase. Boleh pakai koma, contoh 1,5">
                                        <x-fi.input id="discount-value" name="discount_value" inputmode="decimal" placeholder="0"
                                            :value="old('discount_value', $invoice?->discount_value ?? ($isEdit ? 0 : ''))" :disabled="! $hasTotal" />
                                    </x-fi.field>

                                    <p id="discount-error" class="-mt-2 hidden text-sm text-red-600">
                                        <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                                        <span id="discount-error-text">Persentase diskon tidak boleh 100% atau lebih</span>
                                    </p>
                                    <p id="discount-amount-error" class="-mt-2 hidden text-sm text-red-600">
                                        <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                                        <span id="discount-amount-error-text">Nominal diskon tidak boleh lebih dari atau sama dengan total invoice</span>
                                    </p>
                                </div>

                                {{-- DP / Uang Muka --}}
                                <div id="dp-section" @class(['grid content-start gap-y-5 transition-opacity', 'opacity-40' => ! $hasTotal])>
                                    <h3 class="text-sm font-semibold text-gray-950">DP / Uang Muka</h3>

                                    <x-fi.field label="Tipe DP" for="dp-type">
                                        <x-fi.select id="dp-type" name="dp_type" :options="$typeOptions('Tidak Ada DP')"
                                            :value="old('dp_type', $invoice?->dp_type)" :disabled="! $hasTotal" data-fi-select />
                                    </x-fi.field>

                                    <x-fi.field label="Nilai DP" for="dp-value"
                                        hint="Tidak boleh 100% atau lebih untuk persentase. Boleh pakai koma, contoh 1,5">
                                        <x-fi.input id="dp-value" name="dp_value" inputmode="decimal" placeholder="0"
                                            :value="old('dp_value', $invoice?->dp_value ?? ($isEdit ? 0 : ''))" :disabled="! $hasTotal" />
                                    </x-fi.field>

                                    <p id="dp-error" class="-mt-2 hidden text-sm text-red-600">
                                        <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                                        <span id="dp-error-text">Persentase DP tidak boleh 100% atau lebih</span>
                                    </p>
                                    <p id="dp-amount-error" class="-mt-2 hidden text-sm text-red-600">
                                        <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                                        <span id="dp-amount-error-text">Nominal DP tidak boleh lebih dari atau sama dengan total</span>
                                    </p>
                                </div>
                            </div>
                        </x-fi.section>
                    @endunless

                    {{-- SECTION: PPN (opsional; dihitung dari total setelah diskon, tampil di Ringkasan) --}}
                    <x-fi.section heading="PPN" description="Opsional. Bisa diisi setelah ada item dengan total.">
                        <div id="ppn-section" @class(['grid content-start gap-y-5 transition-opacity md:max-w-xs', 'opacity-40' => ! $hasTotal])>
                            <x-fi.field label="Persentase PPN (%)" for="ppn-value"
                                hint="Dihitung dari total setelah diskon. Kosongkan jika tidak dikenakan PPN.">
                                <x-fi.input id="ppn-value" name="ppn" inputmode="decimal" placeholder="Contoh: 11" suffix="%"
                                    :value="old('ppn', $invoice?->ppn ?? '')" :disabled="! $hasTotal" />
                            </x-fi.field>

                            <p id="ppn-error" class="-mt-2 hidden text-sm text-red-600">
                                <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                                <span id="ppn-error-text">PPN tidak boleh 100% atau lebih</span>
                            </p>
                        </div>
                    </x-fi.section>

                    {{-- SECTION: Rekening Pembayaran (minimal 1 dipilih) --}}
                    <x-fi.section heading="Rekening Pembayaran" description="Pilih minimal 1 rekening yang dicantumkan di invoice.">
                        <x-slot:actions>
                            <x-finance.quick-add-payment-account mode="checkbox" checkbox-container="#payment-accounts-list"
                                id-suffix="proyek-invoice" />
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
                </div>

                <div class="grid min-w-0 gap-6 lg:sticky lg:top-0">

                    {{-- SECTION: Ringkasan (dihitung ulang form.js setiap item / diskon / DP / PPN berubah) --}}
                    <x-fi.section heading="Ringkasan">
                        <dl class="grid gap-y-3 text-sm">
                            @if ($isEdit)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-gray-500">Status</dt>
                                    <dd>
                                        <x-fi.badge :color="$invoice->isFullyPaid() ? 'success' : 'warning'">{{ $invoice->payment_status_label }}</x-fi.badge>
                                    </dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Jumlah item</dt>
                                <dd class="font-medium tabular-nums text-gray-950" data-summary="rows">0</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Total invoice</dt>
                                <dd id="invoice-total-preview" class="font-medium tabular-nums text-gray-950">Rp 0</dd>
                            </div>

                            {{-- Muncul bila ada diskon --}}
                            @unless ($isAdmin)
                                <div id="discount-summary" class="hidden">
                                    <div class="grid gap-y-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-gray-500">Diskon</dt>
                                            <dd id="discount-amount" class="font-medium tabular-nums text-red-600">Rp 0</dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-gray-500">Total setelah diskon</dt>
                                            <dd id="total-after-discount" class="font-medium tabular-nums text-emerald-600">Rp 0</dd>
                                        </div>
                                    </div>
                                </div>
                            @endunless

                            {{-- Muncul bila ada PPN --}}
                            <div id="ppn-summary" class="hidden">
                                <div class="grid gap-y-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-gray-500">PPN</dt>
                                        <dd id="ppn-amount" class="font-medium tabular-nums text-red-600">Rp 0</dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-gray-500">Total setelah PPN</dt>
                                        <dd id="total-after-ppn" class="font-medium tabular-nums text-emerald-600">Rp 0</dd>
                                    </div>
                                </div>
                            </div>

                            @unless ($isAdmin)
                                <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-3">
                                    <dt class="font-semibold text-gray-950">Nilai DP</dt>
                                    <dd id="dp-amount" class="text-base font-semibold tabular-nums text-primary">Rp 0</dd>
                                </div>
                            @endunless

                            @if ($isEdit)
                                {{-- Pembayaran dari bukti yang tersimpan (nilai saat ini, belum termasuk perubahan form) --}}
                                <div @class(['flex items-center justify-between gap-3', 'border-t border-gray-200 pt-3' => $isAdmin])>
                                    <dt class="text-gray-500">Terbayar</dt>
                                    <dd class="font-medium tabular-nums text-gray-950">
                                        Rp {{ number_format($invoice->getTotalPaidAmount(), 0, ',', '.') }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-gray-500">Sisa pembayaran</dt>
                                    <dd class="font-medium tabular-nums text-gray-950">
                                        Rp {{ number_format($invoice->getRemainingAmount(), 0, ',', '.') }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </x-fi.section>

                    {{-- SECTION: Tanda Tangan (opsional) --}}
                    <x-fi.section heading="Tanda Tangan" description="Opsional.">
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
            </div>

            {{-- SECTION: Bukti Pembayaran (edit; upload/hapus lewat modal di luar form, langsung tersimpan) --}}
            @if ($isEdit)
                <x-fi.section heading="Bukti Pembayaran"
                    description="Upload/hapus bukti langsung tersimpan dan halaman dimuat ulang — simpan perubahan form terlebih dahulu.">
                    <x-finance.payment-proofs.manager :proofs="$invoice->paymentProofs" invoiceType="proyek"
                        :invoiceKey="$invoice->invoice_number" :manualAmount="true" bare />
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
                <x-fi.button color="gray" :href="route('proyek-invoice.index')">Batal</x-fi.button>
                <x-fi.button type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</x-fi.button>
            </div>
        </form>
    </div>

    {{-- Template baris item baru (dipakai form.js untuk tombol "Tambah Item") --}}
    <template id="item-row-template">
        @include('components.finance.project-invoices.item-row', ['row' => [], 'admin' => $isAdmin])
    </template>

    {{-- Data nomor invoice admin yang sudah dipakai (cek nomor dobel live; lihat initAdminNumberFields di form.js) --}}
    <script type="application/json" id="proyek-invoice-taken-numbers">@json($takenAdminNumbers ?? [])</script>

    {{-- Modal Upload & Hapus Bukti Pembayaran (di luar form agar tidak bersarang) --}}
    @if ($isEdit)
        @include('components.finance.payment-proofs.upload-modal')
        @include('components.finance.payment-proofs.delete-modal')
    @endif

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite(array_values(array_filter([
            'resources/js/pages/finance/project-invoices/form.js',
            $isEdit ? 'resources/js/pages/finance/payment-proofs/embedded.js' : null,
        ])))
    @endpush
@endsection
