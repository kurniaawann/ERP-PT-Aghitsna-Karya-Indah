{{-- =====================================================================
     Halaman: Invoice Alumunium (Finance)
     Tujuan: Daftar invoice alumunium beserta filter bulan/tahun/status &
             pencarian, detail (modal), upload bukti bayar, cetak, dan hapus.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Data dari AlumuniumInvoiceController@index:
     - $invoices : Paginator InvoiceAlumunium hasil
                   AlumuniumInvoiceService::baseQuery($request) dengan filter
                   request('month'), request('year'), request('status'), request('search').

     Komponen yang di-include:
     - x-finance.invoice-tabs (Super Admin)      : tab Barang | Alumunium | Proyek
     - x-finance.aluminium-invoices.table        : tabel (+ filter, pencarian, hapus massal, paginasi)
     - x-finance.aluminium-invoices.detail-modal : modal detail per invoice
     - payment-proofs upload/delete modal        : upload bukti dari menu Aksi
     - x-fi.modal / x-fi.delete-row-modal        : konfirmasi hapus massal / satu invoice

     Tambah/Edit invoice berupa halaman tersendiri: route('alumunium-invoice.create') /
     route('alumunium-invoice.edit') → pages/finance/aluminium-invoice-form.blade.php.

     JS: @vite('resources/js/pages/finance/aluminium-invoices/index.js',
               'resources/js/pages/finance/payment-proofs/embedded.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Alumunium')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Tambah) --}}
        <x-fi.page-header heading="Invoice Alumunium"
            :breadcrumbs="['Finance' => null, 'Invoice Alumunium' => route('alumunium-invoice.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" :href="route('alumunium-invoice.create')">Tambah Invoice</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tab Invoice (Super Admin: satu menu sidebar → beberapa tab) --}}
        @if (auth()->user()->isSuperAdmin())
            <x-finance.invoice-tabs active="alumunium" />
        @endif

        {{-- SECTION: Tabel Invoice Alumunium --}}
        <x-finance.aluminium-invoices.table :invoices="$invoices" />
    </div>

    {{-- SECTION: Modal Detail (satu per invoice) --}}
    @foreach ($invoices as $invoice)
        <x-finance.aluminium-invoices.detail-modal :invoice="$invoice" />
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

    {{-- SECTION: Modal Konfirmasi Hapus 1 Invoice (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu no invoice ke route('alumunium-invoice.destroySelected') yang sama dengan hapus massal. --}}
    <x-fi.delete-row-modal :action="route('alumunium-invoice.destroySelected')" field="selected_invoices"
        heading="Hapus Invoice Alumunium" />

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite(['resources/js/pages/finance/aluminium-invoices/index.js', 'resources/js/pages/finance/payment-proofs/embedded.js'])
    @endpush
@endsection
