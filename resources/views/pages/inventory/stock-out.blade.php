@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Barang Keluar')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Barang Keluar</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('stock-out.index') }}" id="filterForm"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari barang keluar..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <x-buttons.print-dropdown :excelRoute="route('stock-out.export.excel')" :pdfRoute="route('stock-out.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="inline-block min-w-full align-middle">
                <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
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
                        <tbody>
                            @forelse($stockOuts as $record)
                                <tr class="border-t hover:bg-surface-secondary">
                                    <td class="p-2 font-medium text-primary">{{ $record->id_stock_out }}</td>
                                    <td class="p-2">{{ $record->id_item }}</td>
                                    <td class="p-2">{{ $record->item->name_item ?? '-' }}</td>
                                    <td class="p-2 text-center">{{ $record->quantity }}</td>
                                    <td class="p-2 text-center">{{ $record->remaining_quantity ?? '-' }}</td>
                                    <td class="p-2">{{ $record->tanggal->format('d M Y') }}</td>
                                    <td class="p-2">{{ $record->project_name ?? '-' }}</td>
                                </tr>
                            @empty
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

    {{-- Pagination --}}
    <x-pagination :paginator="$stockOuts" />

    {{-- Include Stock Out Scripts --}}
    @include('partials.inventory.stock-out-scripts')
@endsection
