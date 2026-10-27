{{-- =====================================================================
     Halaman: DO Semen (Inventory)
     Tujuan: Halaman utama pengelolaan data semen. Invoice Semen kini
             digenerate langsung dari baris DO Semen (tombol "Buat Invoice"
             pada tiap baris DO) sehingga tidak ada lagi tab terpisah.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Data dari CementDeliveryOrderController@index:
     - $cementDeliveryOrders : LengthAwarePaginator hasil
                 CementDeliveryOrderService::getPaginatedSearch()
     - $paymentAccounts      : Rekening pembayaran aktif (untuk modal
                 "Buat Invoice").
     - $executives           : Petinggi untuk dropdown penandatangan
                 invoice (modal "Buat Invoice").

     Komponen yang di-include:
     - components.inventory.cement-do.table         : tabel DO semen (+ filter,
                 pencarian, hapus massal, paginasi)
     - components.inventory.cement-do.add-modal     : modal tambah DO
     - components.inventory.cement-do.edit-modal    : modal edit DO
     - components.inventory.cement-do.generate-invoice-modal : modal
                 "Buat Invoice" per DO
     - x-fi.page-header, x-fi.export-dropdown, x-fi.button, x-fi.modal,
       x-fi.delete-row-modal (hapus 1 data dari menu Aksi)

     JS yang di-load:
     - @vite('resources/js/pages/inventory/cement-do/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - DO Semen')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Export & Tambah) --}}
        <x-fi.page-header heading="DO Semen"
            subheading="Kelola delivery order semen dan buat invoice semen langsung dari DO."
            :breadcrumbs="['Inventory' => null, 'DO Semen' => route('cement-do.index')]">
            <x-slot:actions>
                {{-- Dropdown Export (PDF & Excel) --}}
                <x-fi.export-dropdown :excelRoute="route('cement-do.export.excel')"
                    :pdfRoute="route('cement-do.export.pdf')" />

                {{-- Tombol Tambah DO Semen --}}
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah DO</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel DO Semen --}}
        @include('components.inventory.cement-do.table', ['cementDeliveryOrders' => $cementDeliveryOrders])
    </div>

    {{-- SECTION: Modal Tambah DO Semen --}}
    @include('components.inventory.cement-do.add-modal')

    {{-- SECTION: Modal Edit DO Semen (satu modal per item) --}}
    @foreach ($cementDeliveryOrders as $cementDeliveryOrder)
        @include('components.inventory.cement-do.edit-modal', ['cementDeliveryOrder' => $cementDeliveryOrder])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus DO Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus semua DO yang dipilih? Semua data semen di dalamnya ikut terhapus.
        Data yang dihapus tidak dapat dikembalikan.
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 DO (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu no DO ke route('cement-does.destroySelected') yang sama dengan hapus massal. --}}
    <x-fi.delete-row-modal :action="route('cement-does.destroySelected')" heading="Hapus DO Semen"
        warning="Semua data semen di dalam DO ini ikut terhapus." />

    {{-- SECTION: Modal "Buat Invoice" (satu modal per baris DO) --}}
    @foreach ($cementDeliveryOrders as $cementDeliveryOrder)
        @include('components.inventory.cement-do.generate-invoice-modal', [
            'cementDeliveryOrder' => $cementDeliveryOrder,
            'paymentAccounts' => $paymentAccounts,
            'executives' => $executives,
        ])
    @endforeach

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/cement-do/index.js')
    @endpush
@endsection
