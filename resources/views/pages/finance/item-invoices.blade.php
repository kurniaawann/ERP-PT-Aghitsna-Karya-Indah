{{-- =====================================================================
     Halaman: Invoice Barang (Item Invoices)
     Tujuan: Menampilkan daftar invoice barang beserta filter bulan/tahun &
             search, serta CRUD (tambah, edit, detail, hapus massal).
     Data dari ItemInvoiceController@index:
     - $invoices       : Paginator InvoiceBarang (10/halaman) dari
                         ItemInvoiceService::baseQuery($request),
                         difilter oleh month, year, search.
     - $items          : Daftar item inventory (Items) dari cache
                         'inventory:items:all', dipakai dropdown di modal
                         dan window._itemsData untuk autofill harga.
     - $paymentAccounts: Rekening pembayaran aktif (PaymentAccountService),
                         untuk dropdown rekening pada modal tambah/edit.
     Komponen yang di-include:
     - x-filters.month-filter / year-filter / search-input : toolbar filter & pencarian
     - x-buttons.delete-button / add-button               : tombol aksi
     - x-finance.item-invoices.table / add-modal / edit-modal / detail-modal : UI CRUD
     - x-pagination                                       : navigasi halaman
     - x-modal                                            : konfirmasi hapus
     JS: @vite('resources/js/pages/finance/item-invoices/index.js')
         (+ window._itemsData via @push('scripts'))
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Barang')

@section('content')
    {{-- ==================== Kontainer Utama Halaman ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Invoice Barang</h1>

        {{-- ==================== Section: Toolbar Filter & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('item-invoice.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau keterangan..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Invoice" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Section: Table ==================== --}}
        <x-finance.item-invoices.table :invoices="$invoices" />
    </div>

    {{-- ==================== Section: Pagination ==================== --}}
    <x-pagination :paginator="$invoices" />

    {{-- ==================== Section: Modals ==================== --}}
    <x-finance.item-invoices.add-modal :items="$items" :paymentAccounts="$paymentAccounts" :executives="$executives" :divisions="$divisions" />

    {{-- Modal Edit & Detail untuk setiap invoice --}}
    @foreach ($invoices as $invoice)
        <x-finance.item-invoices.edit-modal :invoice="$invoice" :items="$items" :paymentAccounts="$paymentAccounts" :executives="$executives" :divisions="$divisions" />
        <x-finance.item-invoices.detail-modal :invoice="$invoice" />
    @endforeach

    {{-- ==================== Section: Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ==================== Section: Scripts (Data Inventory untuk JS) ==================== --}}
    {{-- Data item inventory di-expose ke window._itemsData sebagai array JSON.
         Dipakai JS form tambah/edit untuk autofill harga (capital/selling)
         dan quantity saat memilih item pada dropdown. --}}
    <script>
        window._itemsData = {!! json_encode($items->map(fn($item) => [
            'id_item' => $item->id_item,
            'name_item' => $item->name_item,
            'capital_price' => $item->capital_price,
            'selling_price' => $item->selling_price,
            'quantity' => $item->quantity,
        ])->values()) !!};
    </script>
@endsection

@push('scripts')
    @vite(['resources/js/pages/finance/item-invoices/index.js'])
@endpush
