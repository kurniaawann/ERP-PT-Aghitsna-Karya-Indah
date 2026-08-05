{{-- =====================================================================
     Halaman: Barang Keluar (Stock Out) — read-only
     Tujuan: Menampilkan daftar barang keluar dengan filter bulan/tahun +
             pencarian, dan export PDF/Excel. Tidak ada aksi tambah/edit/
             hapus (data dibuat dari modul penjualan/proyek).
     Data dari ItemStockOutController@index:
     - $stockOuts : LengthAwarePaginator hasil baseQuery (search, month, year)
                    + eager load relasi item, salesRecap, returns.
     Komponen yang di-include:
     - x-filters.*, x-buttons.print-dropdown, x-pagination
     JS: @vite('resources/js/pages/inventory/outgoing-items/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Barang Keluar')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Barang Keluar</h1>

        {{-- SECTION: Filter & Pencarian --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Filter & Pencarian --}}
            <form method="GET" action="{{ route('stock-out.index') }}" id="filterForm"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">

                {{-- Filter Bulan: onchange langsung submit form (#filterForm) = auto filter --}}
                <x-filters.month-filter :value="request('month')" onchange="document.getElementById('filterForm').submit()" responsive="custom" />

                {{-- Filter Tahun: onchange langsung submit form (#filterForm) = auto filter --}}
                <x-filters.year-filter :value="request('year')" onchange="document.getElementById('filterForm').submit()" responsive="custom" />

                {{-- Input Pencarian: kata kunci via request('search') (scope search di model) --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari barang keluar..." responsive="custom" />
            </form>

            {{-- Tombol Export --}}
            {{-- Dropdown Export (PDF & Excel): queryParams membawa filter aktif
                 (search, month, year) agar export konsisten dengan daftar tampil. --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <x-buttons.print-dropdown
                    :excelRoute="route('stock-out.export.excel')"
                    :pdfRoute="route('stock-out.export.pdf')"
                    :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]"
                    responsive="custom" />
            </div>
        </div>

        {{-- SECTION: Tabel Data Barang Keluar --}}
        {{-- Tabel read-only (tanpa checkbox/aksi): menampilkan sisa barang
             ($record->remaining_quantity) dan nama proyek ($record->project_name). --}}
        <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="inline-block min-w-full align-middle">
                <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">

                        {{-- Header Tabel --}}
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="p-2 text-left">ID Keluar</th>
                                <th class="p-2 text-left">ID Barang</th>
                                <th class="p-2 text-left">Nama Barang</th>
                                <th class="p-2 text-center">Jumlah</th>
                                <th class="p-2 text-center">Sisa Barang</th>
                                <th class="p-2 text-left">Tanggal</th>
                                <th class="p-2 text-left">Proyek</th>
                            </tr>
                        </thead>

                        {{-- Body Tabel: iterasi $stockOuts; tiap baris menampilkan record barang keluar.
                             Tanggal diformat 'd M Y', relasi item untuk nama barang. --}}
                        <tbody>
                            @forelse($stockOuts as $record)
                                <tr class="border-t hover:bg-surface-secondary">
                                    <td class="p-2 font-medium text-primary">{{ $record->id_stock_out }}</td>
                                    <td class="p-2">{{ $record->id_item }}</td>
                                    <td class="p-2">{{ $record->item->name_item ?? '-' }}</td>
                                    <td class="p-2 text-center">{{ $record->quantity }}</td>
                                    <td class="p-2 text-center">{{ $record->remaining_quantity ?? '-' }}</td>
                                    <td class="p-2">{{ $record->date->format('d M Y') }}</td>
                                    <td class="p-2">{{ $record->project_name ?? '-' }}</td>
                                </tr>
                            @empty
                                {{-- Pesan jika data kosong --}}
                                <tr>
                                    <td colspan="7" class="text-center p-4 text-text-secondary">
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- SECTION: Pagination --}}
    {{-- Paginasi data $stockOuts (LengthAwarePaginator, 15 per halaman) --}}
    <x-pagination :paginator="$stockOuts" />

    {{-- SECTION: Scripts --}}
    {{-- Modul outgoing-items/index.js dipakai untuk keperluan interaksi halaman (mis. export). --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/outgoing-items/index.js')
    @endpush
@endsection
