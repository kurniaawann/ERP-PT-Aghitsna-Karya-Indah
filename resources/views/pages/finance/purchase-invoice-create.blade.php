{{-- =====================================================================
     Halaman: Tambah Faktur Pembelian (Finance)
     Tujuan: Pengganti modal tambah Faktur Pembelian — repeater kartu faktur
             (bisa banyak faktur sekaligus, kartu bisa ditutup/dibuka, salin
             dari faktur ke-1) dan ringkasan total dalam satu halaman.
             Edit tetap memakai modal di halaman daftar (1 record = 1 faktur).

     Data dari PurchaseInvoiceController@create: tidak ada (form kosong).

     Submit ke route('purchase-invoice.store') (POST) sebagai array
     invoices[i][field]; PPN dihitung ulang di server
     (PurchaseInvoiceService::createInvoices). Berhasil → kembali ke daftar;
     gagal validasi → kembali ke halaman ini dengan old('invoices') (kartu
     ikut dipulihkan + pesan error per field).

     JS yang di-load:
     - @vite('resources/js/pages/finance/purchase-invoices/create.js')
     ===================================================================== --}}
@extends('layouts.app')

@php
    // Kartu awal: input lama (penyimpanan gagal, indeks asli dipertahankan
    // agar pesan error invoices.{i}.field cocok) → 1 kartu kosong
    $oldInvoices = session()->hasOldInput('invoices') ? array_filter((array) old('invoices'), 'is_array') : [];
@endphp

@section('title', 'PT Aghitsna Karya Indah - Tambah Faktur Pembelian')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header heading="Tambah Faktur Pembelian"
            :breadcrumbs="['Finance' => null, 'Faktur Pembelian' => route('purchase-invoice.index'), 'Tambah' => null]" />

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        <form id="purchase-invoice-create-form" method="POST" action="{{ route('purchase-invoice.store') }}"
            class="flex flex-col gap-y-6">
            @csrf

            {{-- Desktop: kartu faktur di kiri (2 kolom), Ringkasan menempel di kanan.
                 Mobile: kartu faktur → Ringkasan. --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- SECTION: Daftar Faktur (repeater kartu; kartu baru ditambah JS) --}}
                <x-fi.section heading="Data Faktur"
                    description="Tambahkan lebih dari satu faktur sekaligus; klik judul kartu untuk menutup/membuka."
                    class="lg:col-span-2">
                    {{-- grid-cols-1 (minmax(0,1fr)) agar ringkasan panjang di judul kartu
                         terpotong (truncate), bukan melebarkan kartu di layar kecil --}}
                    <div id="purchaseInvoicesContainer" class="grid grid-cols-1 gap-y-4">
                        @forelse ($oldInvoices as $index => $old)
                            @include('components.finance.purchase-invoices.row-form', ['index' => $index, 'old' => $old])
                        @empty
                            @include('components.finance.purchase-invoices.row-form', ['index' => 0, 'old' => null])
                        @endforelse
                    </div>

                    <x-fi.button color="gray" icon="plus" class="mt-4 w-full" onclick="addInvoiceCard()">Tambah Faktur</x-fi.button>
                </x-fi.section>

                {{-- SECTION: Ringkasan (dihitung ulang create.js; menempel saat scroll) --}}
                <x-fi.section heading="Ringkasan" class="self-start lg:sticky lg:top-0">
                    <dl class="grid gap-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Jumlah faktur</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="count">0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Total harga jual</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="selling">Rp 0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Total PPN</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="ppn">Rp 0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-3">
                            <dt class="font-semibold text-gray-950">Total</dt>
                            <dd class="text-base font-semibold tabular-nums text-gray-950" data-summary="total">Rp 0</dd>
                        </div>
                    </dl>
                </x-fi.section>
            </div>

            {{-- SECTION: Aksi Form --}}
            <div class="flex flex-wrap items-center gap-3">
                <x-fi.button type="submit">Simpan</x-fi.button>
                <x-fi.button color="gray" :href="route('purchase-invoice.index')">Batal</x-fi.button>
            </div>
        </form>
    </div>

    {{-- Template kartu faktur baru ('__INDEX__' diganti indeks unik oleh create.js) --}}
    <template id="purchaseInvoiceRowTemplate">
        @include('components.finance.purchase-invoices.row-form', ['index' => '__INDEX__', 'old' => null])
    </template>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/finance/purchase-invoices/create.js')
    @endpush
@endsection
