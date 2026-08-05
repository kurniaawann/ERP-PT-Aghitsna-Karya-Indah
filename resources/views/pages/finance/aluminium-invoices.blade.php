{{-- =====================================================================
     Halaman: Invoice Alumunium
     Tujuan: Menampilkan daftar invoice alumunium dengan filter bulan/tahun
             dan pencarian, lengkap dengan aksi tambah, edit, detail,
             serta hapus massal (bulk delete).
     Data dari AlumuniumInvoiceController@index:
     - $invoices       : Paginator InvoiceAlumunium (15/halaman) hasil
                         AlumuniumInvoiceService::baseQuery($request) dengan
                         filter request('month'), request('year'), request('search').
     - $paymentAccounts: Koleksi rekening pembayaran aktif dari
                         PaymentAccountService::getActiveAccounts(), dipakai
                         untuk dropdown rekening di modal tambah/edit.
     Komponen yang di-include:
     - x-filters.month-filter / year-filter / search-input : toolbar filter & pencarian
     - x-buttons.delete-button / add-button               : tombol aksi hapus & tambah
     - x-finance.aluminium-invoices.table                 : tabel daftar invoice
     - x-finance.aluminium-invoices.add-modal / edit-modal / detail-modal : modal CRUD
     - x-pagination                                       : navigasi halaman
     - x-modal                                            : modal konfirmasi hapus
     JS: @vite('resources/js/pages/finance/aluminium-invoices/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Alumunium')

@section('content')
    {{-- ==================== Kontainer Utama Halaman ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Invoice Alumunium</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('alumunium-invoice.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari no invoice atau kepada..." responsive="custom" />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Invoice" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Section: Table ==================== --}}
        {{-- Table Component --}}
        {{-- Table Component: menampilkan list invoice alumunium beserta
             checkbox untuk seleksi hapus massal. --}}
        <x-finance.aluminium-invoices.table :invoices="$invoices" />

    </div>

    {{-- ==================== Section: Pagination ==================== --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$invoices" />

    {{-- ==================== Section: Modals ==================== --}}
    {{-- Modal Tambah Invoice --}}
    {{-- Modal Tambah Invoice: form input invoice alumunium baru, memakai
         daftar rekening pembayaran aktif untuk field pembayaran. --}}
    <x-finance.aluminium-invoices.add-modal :paymentAccounts="$paymentAccounts" />

    {{-- Modal Edit & Detail untuk setiap invoice --}}
    {{-- Loop untuk membuat satu pasang modal Edit & Detail per invoice.
         Setiap invoice punya modal sendiri agar data terpisah dan form
         edit tidak tercampur antar baris. --}}
    @foreach ($invoices as $invoice)
        <x-finance.aluminium-invoices.edit-modal :invoice="$invoice" :paymentAccounts="$paymentAccounts" />
        <x-finance.aluminium-invoices.detail-modal :invoice="$invoice" />
    @endforeach

    {{-- ==================== Section: Modal Konfirmasi Bulk Delete ==================== --}}
    {{-- Modal Konfirmasi Bulk Delete --}}
    {{-- Dikonfirmasi lewat submitDeleteForm(); hanya invoice yang
         tercentang (selected_invoices) yang akan dihapus. --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ==================== Section: JavaScript ==================== --}}
    {{-- JavaScript --}}
    @vite('resources/js/pages/finance/aluminium-invoices/index.js')
@endsection
