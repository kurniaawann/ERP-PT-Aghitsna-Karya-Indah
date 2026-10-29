{{-- =====================================================================
     Halaman: Faktur Pembelian (Finance)
     Tujuan: Daftar faktur pembelian dengan filter bulan/tahun & pencarian
             (material, barang, NPWP), export Excel/PDF, edit (modal),
             cetak PDF per faktur, dan hapus (per baris & massal).
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Data dari PurchaseInvoiceController@index:
     - $invoices : Paginator PurchaseInvoice (15/halaman) hasil
                   PurchaseInvoiceService::buildFilteredQuery($request),
                   urut created_at desc.

     Komponen yang di-include:
     - x-finance.purchase-invoices.table      : tabel (+ filter, pencarian, hapus massal, paginasi)
     - x-finance.purchase-invoices.edit-modal : modal edit per faktur
     - x-fi.modal / x-fi.delete-row-modal     : konfirmasi hapus massal / satu faktur

     Tambah faktur berupa halaman tersendiri: route('purchase-invoice.create')
     → pages/finance/purchase-invoice-create.blade.php.

     JS: @vite('resources/js/pages/finance/purchase-invoices/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Faktur Pembelian')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Export & Tambah) --}}
        <x-fi.page-header heading="Faktur Pembelian"
            :breadcrumbs="['Finance' => null, 'Faktur Pembelian' => route('purchase-invoice.index')]">
            <x-slot:actions>
                {{-- Dropdown Export (PDF & Excel, mengikuti filter aktif) --}}
                <x-fi.export-dropdown :excelRoute="route('purchase-invoice.export-excel')"
                    :pdfRoute="route('purchase-invoice.export-pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                    ]" />

                <x-fi.button icon="plus" :href="route('purchase-invoice.create')">Tambah Faktur</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel Faktur Pembelian --}}
        <x-finance.purchase-invoices.table :invoices="$invoices" />
    </div>

    {{-- SECTION: Modal Edit (satu per faktur) --}}
    @foreach ($invoices as $invoice)
        <x-finance.purchase-invoices.edit-modal :invoice="$invoice" />
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Faktur Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus semua faktur yang dipilih? Data yang dihapus tidak dapat dikembalikan.
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Faktur (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('purchase-invoice.destroy-selected') yang sama dengan hapus massal. --}}
    <x-fi.delete-row-modal :action="route('purchase-invoice.destroy-selected')" field="selected_invoices"
        heading="Hapus Faktur Pembelian" />

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        <script>
            // Modal edit yang dibuka ulang karena penyimpanan gagal (editModal-{id})
            window.PURCHASE_INVOICE_DATA = {
                reopenModal: @json($errors->any() ? old('_modal') : null),
            };
        </script>

        @vite('resources/js/pages/finance/purchase-invoices/index.js')
    @endpush
@endsection
