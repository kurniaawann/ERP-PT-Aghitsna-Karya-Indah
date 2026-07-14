@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Penjualan')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ==================== Header Rekap Penjualan ==================== --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Penjualan</h1>

        {{-- ==================== Toolbar Filter & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('recap-sales.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" />
                <x-filters.year-filter :value="request('year')" />
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek..." />
            </form>

            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('recap-sales.export.excel')" :pdfRoute="route('recap-sales.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Laporan" />
                </div>
            </div>
        </div>

        {{-- ==================== Tabel Rekap Penjualan ==================== --}}
        @include('components.finance.sales-recaps.table', [
            'salesRecaps' => $salesRecaps,
            'grandTotals' => $grandTotals,
        ])
    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$salesRecaps" />

    {{-- ==================== Modal Tambah ==================== --}}
    @include('components.finance.sales-recaps.add-modal', ['items' => $items])

    {{-- ==================== Modal Edit & Status (hanya untuk yang belum lunas) ==================== --}}
    @foreach ($salesRecaps as $sale)
        @if (!$sale->isLunas())
            @include('components.finance.sales-recaps.edit-modal', ['sale' => $sale, 'items' => $items])
            @include('components.finance.sales-recaps.status-modal', ['sale' => $sale])
        @endif
    @endforeach

    {{-- ==================== Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah Anda yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ==================== JavaScript ==================== --}}
    <script>
        window._itemsData = {!! json_encode($items->map(fn($item) => [
            'id_item' => $item->id_item,
            'name_item' => $item->name_item,
            'capital_price' => $item->capital_price,
            'selling_price' => $item->selling_price,
            'quantity' => $item->quantity,
        ])->values()) !!};
    </script>
    @vite(['resources/js/pages/finance/sales-recaps/index.js'])
@endsection
