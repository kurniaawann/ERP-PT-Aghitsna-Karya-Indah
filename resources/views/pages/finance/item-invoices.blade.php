{{-- =====================================================================
     Halaman: Invoice Barang (Finance)
     Tujuan: Daftar invoice barang beserta filter bulan/tahun/status &
             pencarian, detail (modal), upload bukti bayar, cetak, dan hapus.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Data dari ItemInvoiceController@index:
     - $invoices : Paginator InvoiceBarang (10/halaman) dari
                   ItemInvoiceService::baseQuery($request) (+ salesRecap,
                   paymentProofs, signedBy, division).

     Komponen yang di-include:
     - x-finance.invoice-tabs (Super Admin) : tab Barang | Alumunium | Proyek
     - x-finance.item-invoices.table        : tabel (+ filter, pencarian, hapus massal, paginasi)
     - x-finance.item-invoices.detail-modal : modal detail per invoice
     - payment-proofs upload/delete modal   : upload bukti dari menu Aksi
     - x-fi.modal / x-fi.delete-row-modal   : konfirmasi hapus massal / satu invoice

     Tambah/Edit invoice berupa halaman tersendiri: route('item-invoice.create') /
     route('item-invoice.edit') → pages/finance/item-invoice-form.blade.php.

     JS: @vite('resources/js/pages/finance/item-invoices/index.js',
               'resources/js/pages/finance/payment-proofs/embedded.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Barang')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Tambah) --}}
        <x-fi.page-header heading="Invoice Barang"
            :breadcrumbs="['Finance' => null, 'Invoice Barang' => route('item-invoice.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" :href="route('item-invoice.create')">Tambah Invoice</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tab Invoice (Super Admin: satu menu sidebar → beberapa tab) --}}
        @if (auth()->user()->isSuperAdmin())
            <x-finance.invoice-tabs active="barang" />
        @endif

        {{-- SECTION: Tabel Invoice Barang --}}
        <x-finance.item-invoices.table :invoices="$invoices" />
    </div>

    {{-- SECTION: Modal Detail (satu per invoice) --}}
    @foreach ($invoices as $invoice)
        <x-finance.item-invoices.detail-modal :invoice="$invoice" />
    @endforeach

    {{-- SECTION: Modal Upload & Hapus Bukti Pembayaran (dipicu menu Aksi) --}}
    @include('components.finance.payment-proofs.upload-modal')
    @include('components.finance.payment-proofs.delete-modal')

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Invoice Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus semua invoice yang dipilih? Rekap penjualan terkait ikut terhapus dan
        stok barang dikembalikan. Data yang dihapus tidak dapat dikembalikan.
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Invoice (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu no invoice ke route('item-invoice.destroySelected') yang sama dengan hapus massal. --}}
    <x-fi.delete-row-modal :action="route('item-invoice.destroySelected')" field="selected_invoices"
        heading="Hapus Invoice Barang" warning="Rekap penjualan terkait ikut terhapus dan stok barang dikembalikan." />

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite(['resources/js/pages/finance/item-invoices/index.js', 'resources/js/pages/finance/payment-proofs/embedded.js'])
    @endpush
@endsection
