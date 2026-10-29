{{-- =====================================================================
     Halaman: Invoice Proyek (Finance)
     Tujuan: Daftar invoice proyek beserta filter bulan/tahun/status &
             pencarian, detail (modal), upload bukti bayar, cetak, dan hapus.
             Judul menyesuaikan role: admin "Invoice", non-admin "Invoice Proyek".
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Data dari ProyekInvoiceController@index:
     - $invoices : Paginator InvoiceProyek hasil ProyekInvoiceService::baseQuery($request)
                   (+ paymentProofs, projectRecap.invoices), difilter month, year,
                   status, search.

     Komponen yang di-include:
     - x-finance.invoice-tabs (Super Admin)    : tab Barang | Alumunium | Proyek
     - x-finance.project-invoices.table        : tabel (+ filter, pencarian, hapus massal, paginasi)
     - x-finance.project-invoices.detail-modal : modal detail per invoice
     - x-finance.project-invoices.delete-modal : konfirmasi hapus 1 invoice (menu Aksi)
     - payment-proofs upload/delete modal      : upload bukti dari menu Aksi
     - x-fi.modal                              : konfirmasi hapus massal

     Tambah/Edit invoice berupa halaman tersendiri: route('proyek-invoice.create') /
     route('proyek-invoice.edit') → pages/finance/project-invoice-form.blade.php.

     JS: @vite('resources/js/pages/finance/project-invoices/index.js',
               'resources/js/pages/finance/payment-proofs/embedded.js')
     ===================================================================== --}}
@extends('layouts.app')

@php
    $label = auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek';
@endphp

@section('title', 'PT Aghitsna Karya Indah - ' . $label)

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Tambah) --}}
        <x-fi.page-header :heading="$label"
            :breadcrumbs="['Finance' => null, $label => route('proyek-invoice.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" :href="route('proyek-invoice.create')">Tambah Invoice</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tab Invoice (Super Admin: satu menu sidebar → beberapa tab) --}}
        @if (auth()->user()->isSuperAdmin())
            <x-finance.invoice-tabs active="proyek" />
        @endif

        {{-- SECTION: Tabel Invoice Proyek --}}
        <x-finance.project-invoices.table :invoices="$invoices" />
    </div>

    {{-- SECTION: Modal Detail & Hapus (satu per invoice) --}}
    @foreach ($invoices as $invoice)
        <x-finance.project-invoices.detail-modal :invoice="$invoice" />
        <x-finance.project-invoices.delete-modal :invoice="$invoice" />
    @endforeach

    {{-- SECTION: Modal Upload & Hapus Bukti Pembayaran (dipicu menu Aksi) --}}
    @include('components.finance.payment-proofs.upload-modal')
    @include('components.finance.payment-proofs.delete-modal')

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    {{-- Dikonfirmasi lewat submitDeleteForm(); hanya invoice yang tercentang (selected_invoices) yang dihapus. --}}
    <x-fi.modal id="deleteModal" heading="Hapus Invoice Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus semua invoice yang dipilih? Data yang dihapus tidak dapat dikembalikan.
    </x-fi.modal>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite(['resources/js/pages/finance/project-invoices/index.js', 'resources/js/pages/finance/payment-proofs/embedded.js'])
    @endpush
@endsection
