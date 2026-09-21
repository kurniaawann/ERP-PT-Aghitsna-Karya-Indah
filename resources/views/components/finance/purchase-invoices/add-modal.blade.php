{{-- =====================================================================
     Modal Tambah Faktur Pembelian (mendukung multiple record + collapse).

     Mendukung penambahan banyak faktur sekaligus:
     - Kartu faktur pertama dirender statis di Blade (index 0).
     - Tombol "Tambah Faktur" menambah kartu baru dari template
       purchaseInvoiceRowTemplate (placeholder '__INDEX__').
     - Setiap kartu dapat ditutup (collapse) lewat klik pada header.
     - Kartu ke-2 dst memiliki checkbox "Sama dengan Faktur ke-1" untuk
       menyalin semua kolom dari faktur pertama.
     - Data dikirim sebagai array `invoices[]` dan diproses massal oleh
       PurchaseInvoiceService::createInvoices().
     - JS: resources/js/pages/finance/purchase-invoices/index.js
     ===================================================================== --}}
<x-modal id="addModal" title="Tambah Faktur Pembelian" action="{{ route('purchase-invoice.store') }}" method="POST"
    buttonText="Simpan" size="4xl">

    <p class="text-sm text-text-secondary mb-3">
        Isi data faktur pembelian. Untuk menambahkan lebih dari satu faktur sekaligus klik
        "Tambah Faktur". Setiap faktur dapat ditutup dengan mengklik header kartu agar hemat tempat.
    </p>

    {{-- Container Kartu Faktur --}}
    <div id="purchaseInvoicesContainer" class="space-y-4">
        @include('components.finance.purchase-invoices.row-form', ['index' => 0])
    </div>

    {{-- Tombol Tambah Faktur --}}
    <button type="button" onclick="addInvoiceCard()"
        class="mt-1 w-full flex items-center justify-center gap-2 border-2 border-dashed border-primary text-primary rounded-lg p-3 hover:bg-primary-light transition-colors">
        <i class="fa-solid fa-plus"></i> Tambah Faktur
    </button>

</x-modal>

{{-- Template kartu faktur untuk penambahan dinamis.
     Placeholder '__INDEX__' diganti dengan nomor urut oleh JavaScript. --}}
<template id="purchaseInvoiceRowTemplate">
    @include('components.finance.purchase-invoices.row-form', ['index' => '__INDEX__'])
</template>