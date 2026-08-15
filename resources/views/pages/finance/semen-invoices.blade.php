{{-- =====================================================================
     Halaman: Invoice Semen (Finance)
     Tujuan: Menampilkan daftar invoice semen beserta filter bulan/tahun &
             pencarian, serta CRUD (tambah, edit, detail, hapus massal).
     Data dari SemenInvoiceController@index:
     - $invoices        : Paginator InvoiceSemen (10/halaman) dari
                          SemenInvoiceService::baseQuery($request),
                          difilter oleh month, year, search.
     - $paymentAccounts : Rekening pembayaran aktif (PaymentAccountService),
                          dipilih per proyek di dalam modal tambah/edit.
     Data Semen (tabel `cements`) untuk dropdown tiap baris dimuat dinamis
     via AJAX (GET /semen-invoice/cements-data), sehingga selalu mutakhir.
     Komponen yang di-include:
     - x-filters.month-filter / year-filter / search-input : toolbar filter & pencarian
     - x-buttons.print-dropdown / delete-button / add-button     : tombol aksi
     - x-finance.semen-invoices.table / add-modal / edit-modal / detail-modal : UI CRUD
     - x-pagination                                    : navigasi halaman
     - x-modal                                         : konfirmasi hapus
     JS: @vite('resources/js/pages/finance/semen-invoices/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Semen')

@section('content')
    {{-- ==================== Kontainer Utama Halaman ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Invoice Semen</h1>

        {{-- ==================== Section: Toolbar Filter & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('semen-invoice.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari no invoice, proyek, atau nama..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Invoice" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Section: Table ==================== --}}
        <x-finance.semen-invoices.table :invoices="$invoices" />
    </div>

    {{-- ==================== Section: Pagination ==================== --}}
    <x-pagination :paginator="$invoices" />

    {{-- ==================== Section: Modals ==================== --}}
    <x-finance.semen-invoices.add-modal :paymentAccounts="$paymentAccounts" :executives="$executives" />

    {{-- Modal Edit (tunggal, diisi via AJAX) --}}
    <x-finance.semen-invoices.edit-modal :paymentAccounts="$paymentAccounts" :executives="$executives" />

    {{-- Modal Detail untuk setiap invoice --}}
    @foreach ($invoices as $invoice)
        <x-finance.semen-invoices.detail-modal :invoice="$invoice" />
    @endforeach

    {{-- ==================== Section: Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>
@endsection

@push('scripts')
    @vite(['resources/js/pages/finance/semen-invoices/index.js'])
@endpush