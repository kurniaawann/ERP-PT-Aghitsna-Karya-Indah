{{-- =====================================================================
     Konten: Rekap Penjualan (gaya Filament)
     Dipakai di dua tempat:
     - pages/finance/rekap.blade.php (Super Admin, tab "sales"; $tabs & $tab
       tersedia → judul "Rekap" + tab, filter ke route('rekap.index') + tab)
     - pages/finance/sales-recaps.blade.php (halaman Rekap Penjualan sendiri)

     Data dari RecapSalesController::indexData():
     - $salesRecaps : Paginator SalesRecap (10/halaman)
     - $items       : Daftar item inventory (tidak dipakai di sini; form
                      tambah/edit kini halaman tersendiri)
     - $grandTotals : Grand totals (kapital, penjualan, laba)

     Tambah/Edit laporan berupa halaman tersendiri: route('recap-sales.create') /
     route('recap-sales.edit') → pages/finance/sales-recap-form.blade.php.

     JS: @vite('resources/js/pages/finance/sales-recaps/index.js',
               'resources/js/pages/finance/payment-proofs/embedded.js')
     ===================================================================== --}}
@php
    $inRekap = isset($tabs);
    $indexUrl = $inRekap ? route('rekap.index', ['tab' => 'sales']) : route('recap-sales.index');
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Header Halaman + Aksi (Export & Tambah) --}}
    <x-fi.page-header :heading="$inRekap ? 'Rekap' : 'Rekap Penjualan'"
        :breadcrumbs="$inRekap
            ? ['Finance' => null, 'Rekap' => route('rekap.index'), 'Rekap Penjualan' => null]
            : ['Finance' => null, 'Rekap Penjualan' => $indexUrl]">
        <x-slot:actions>
            {{-- Dropdown Export (Excel & PDF, mengikuti filter aktif) --}}
            <x-fi.export-dropdown :excelRoute="route('recap-sales.export.excel')" :pdfRoute="route('recap-sales.export.pdf')"
                :queryParams="[
                    'search' => request('search'),
                    'month' => request('month'),
                    'year' => request('year'),
                    'status' => request('status'),
                ]" />

            <x-fi.button icon="plus" :href="route('recap-sales.create')">Tambah Laporan</x-fi.button>
        </x-slot:actions>
    </x-fi.page-header>

    {{-- SECTION: Tab Rekap (Super Admin) --}}
    @if ($inRekap)
        <x-finance.recap-tabs :tabs="$tabs" active="sales" />
    @endif

    {{-- SECTION: Tabel Rekap Penjualan (+ filter, pencarian, hapus massal, total, paginasi) --}}
    @include('components.finance.sales-recaps.table', [
        'salesRecaps' => $salesRecaps,
        'grandTotals' => $grandTotals,
        'indexUrl' => $indexUrl,
        'filterTab' => $inRekap ? 'sales' : null,
    ])
</div>

{{-- SECTION: Modal Status (hanya untuk yang belum lunas) --}}
@foreach ($salesRecaps as $sale)
    @if (!$sale->isLunas())
        @include('components.finance.sales-recaps.status-modal', ['sale' => $sale])
    @endif
@endforeach

{{-- SECTION: Modal Upload & Hapus Bukti Pembayaran (dipicu menu Aksi) --}}
@include('components.finance.payment-proofs.upload-modal')
@include('components.finance.payment-proofs.delete-modal')

{{-- SECTION: Modal Konfirmasi Hapus Massal --}}
<x-fi.modal id="deleteModal" heading="Hapus Laporan Terpilih" icon="trash" color="danger" width="md"
    onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
    Apakah Anda yakin ingin menghapus data yang dipilih?
</x-fi.modal>

{{-- SECTION: Modal Konfirmasi Hapus 1 Laporan (menu Aksi ⋮ tiap baris) --}}
{{-- Mengirim satu id ke route('recap-sales.destroySelected') yang sama dengan hapus massal. --}}
<x-fi.delete-row-modal :action="route('recap-sales.destroySelected')" field="selected_sales"
    heading="Hapus Rekap Penjualan" />

@push('scripts')
    @vite(['resources/js/pages/finance/sales-recaps/index.js', 'resources/js/pages/finance/payment-proofs/embedded.js'])
@endpush
