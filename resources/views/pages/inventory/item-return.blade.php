@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Pengembalian Barang')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Pengembalian Barang</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('item-return.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Return Type --}}
                <select name="return_type"
                    class="px-3 py-2 rounded-lg border-2 border-border text-sm font-medium text-text-primary bg-white hover:border-border-hover transition-colors duration-150">
                    <option value="">Semua Tipe</option>
                    <option value="masuk" @selected(request('return_type') === 'masuk')>Pengembalian Masuk</option>
                    <option value="keluar" @selected(request('return_type') === 'keluar')>Pengembalian Keluar</option>
                </select>

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari return barang..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('item-return.export.excel')" :pdfRoute="route('item-return.export.pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                        'return_type' => request('return_type'),
                    ]" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Pengembalian" />
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="inline-block min-w-full align-middle">
                <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="p-2 text-left">ID Return</th>
                                <th class="p-2 text-left">ID Barang</th>
                                <th class="p-2 text-left">Nama Barang</th>
                                <th class="p-2 text-center">Jumlah</th>
                                <th class="p-2 text-left">Alasan</th>
                                <th class="p-2 text-center">Tipe</th>
                                <th class="p-2 text-left">Tanggal</th>
                                <th class="p-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($returns as $record)
                                <tr class="border-t hover:bg-surface-secondary">
                                    <td class="p-2 font-medium text-primary">{{ $record->id_return }}</td>
                                    <td class="p-2">{{ $record->id_item }}</td>
                                    <td class="p-2">{{ $record->item->name_item ?? '-' }}</td>
                                    <td class="p-2 text-center">{{ $record->quantity }}</td>
                                    <td class="p-2 text-sm">{{ $record->alasan ?? '-' }}</td>
                                    <td class="p-2 text-center">
                                        @if ($record->return_type === 'masuk')
                                            <span
                                                class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">Masuk</span>
                                        @else
                                            <span
                                                class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium">Keluar</span>
                                        @endif
                                    </td>
                                    <td class="p-2">{{ $record->tanggal->format('d M Y') }}</td>
                                    <td class="p-2 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="openModal('editModal-{{ $record->id_return }}')"
                                                class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors text-xs">
                                                <i class="fa-solid fa-pen w-3 h-3"></i> Edit
                                            </button>
                                            <button type="button"
                                                onclick="deleteRecord('{{ route('item-return.destroy', $record->id_return) }}')"
                                                class="flex items-center gap-1 bg-btn-delete hover:bg-btn-delete-hover text-white px-2 py-1 rounded-lg transition-colors text-xs">
                                                <i class="fa-solid fa-trash w-3 h-3"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center p-4 text-text-secondary">
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <x-pagination :paginator="$returns" />
    </div>

    {{-- Modal Tambah --}}
    @include('components.inventory.item-return.add-modal', [
        'items' => $items,
        'stockOuts' => $stockOuts,
        'stockIns' => $stockIns,
    ])

    {{-- Modal Edit untuk setiap record --}}
    @foreach ($returns as $record)
        @include('components.inventory.item-return.edit-modal', [
            'record' => $record,
            'stockOuts' => $stockOuts,
            'stockIns' => $stockIns,
        ])
    @endforeach

    {{-- Include Item Return Scripts --}}
    @include('partials.inventory.item-return-scripts')
@endsection
