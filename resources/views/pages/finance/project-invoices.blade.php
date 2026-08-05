{{-- =====================================================================
     Halaman: Invoice Proyek
     Tujuan: Menampilkan daftar invoice proyek dengan filter bulan/tahun &
             pencarian, serta aksi tambah, edit, detail, hapus per baris,
             dan hapus massal. Judul & konten menyesuaikan role:
             admin menamainya "Invoice", non-admin "Invoice Proyek".
     Data dari ProyekInvoiceController@index:
     - $invoices       : Paginator InvoiceProyek (15/halaman) hasil
                         ProyekInvoiceService::baseQuery($request),
                         difilter oleh month, year, search.
     - $paymentAccounts: Rekening pembayaran aktif (PaymentAccountService)
                         untuk dropdown rekening di modal tambah/edit.
     Komponen yang di-include:
     - x-filters.month-filter / year-filter / search-input : toolbar filter & pencarian
     - x-buttons.delete-button / add-button               : tombol aksi
     - x-finance.project-invoices.table / add-modal / edit-modal / detail-modal / delete-modal : UI CRUD
     - x-pagination                                       : navigasi halaman
     - x-modal                                            : konfirmasi hapus
     JS: @vite('resources/js/pages/finance/project-invoices/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - ' . (auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek'))

@section('content')
    {{-- Header Invoice Proyek --}}
    {{-- Alur auth()->user()->isAdmin(): jika admin tampilkan label
         "Invoice", selain admin tampilkan "Invoice Proyek". --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">{{ auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek' }}</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('proyek-invoice.index') }}"
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
        {{-- Table Component: daftar invoice proyek dengan checkbox seleksi
             dan tombol aksi per baris. --}}
        <x-finance.project-invoices.table :invoices="$invoices" />
    </div>

    {{-- ==================== Section: Pagination ==================== --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$invoices" />

    {{-- ==================== Section: Modals ==================== --}}
    {{-- Modal Tambah Invoice --}}
    <x-finance.project-invoices.add-modal :paymentAccounts="$paymentAccounts" />

    {{-- Modal Edit & Detail untuk setiap invoice --}}
    {{-- Modal Edit, Detail, & Delete untuk setiap invoice: satu set modal
         dibuat per baris agar data tiap invoice tetap terpisah. --}}
    @foreach ($invoices as $invoice)
        <x-finance.project-invoices.edit-modal :invoice="$invoice" :paymentAccounts="$paymentAccounts" />
        <x-finance.project-invoices.detail-modal :invoice="$invoice" />
        <x-finance.project-invoices.delete-modal :invoice="$invoice" />
    @endforeach

    {{-- ==================== Section: Modal Konfirmasi Bulk Delete ==================== --}}
    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ==================== Section: JavaScript ==================== --}}
    {{-- JavaScript --}}
    @vite('resources/js/pages/finance/project-invoices/index.js')
@endsection
