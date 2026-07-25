@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Barang Masuk')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Barang Masuk</h1>

        {{-- Toolbar: Filter, Pencarian & Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Filter & Pencarian --}}
            <form method="GET" action="{{ route('stock-in.index') }}" id="filterForm"
                class="w-full xl:w-auto xl:flex-1 flex flex-col xl:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Input Pencarian --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari barang masuk..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 xl:mt-0 w-full xl:w-auto">
                <div class="flex flex-col xl:flex-row gap-2 w-full xl:w-auto">

                    {{-- Dropdown Export (PDF & Excel) --}}
                    <x-buttons.print-dropdown :excelRoute="route('stock-in.export.excel')" :pdfRoute="route('stock-in.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Barang Masuk --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Barang Masuk" />
                </div>
            </div>
        </div>

        {{-- Tabel Data Barang Masuk --}}
        @include('components.inventory.stock-in.table', ['stockIns' => $stockIns])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$stockIns" />

    {{-- Modal Tambah Barang Masuk --}}
    @include('components.inventory.stock-in.add-modal', ['items' => $items])

    {{-- Modal Edit Barang Masuk (satu modal per record) --}}
    @foreach ($stockIns as $record)
        @include('components.inventory.stock-in.edit-modal', ['record' => $record, 'allItems' => $items])
    @endforeach

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Data Items untuk JavaScript --}}
    @push('scripts')
        <script>
            window.STOCK_IN_ITEMS = {!! json_encode($items->map(fn($item) => [
                'id_item' => $item->id_item,
                'name_item' => $item->name_item,
                'capital_price' => $item->capital_price,
                'quantity' => $item->quantity,
            ])) !!};
        </script>

        @vite('resources/js/pages/inventory/incoming-items/index.js')
    @endpush
@endsection
