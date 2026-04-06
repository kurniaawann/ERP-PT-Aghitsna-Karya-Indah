@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Barang Masuk')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Barang Masuk</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('stock-in.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari barang masuk..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('stock-in.export.excel')" :pdfRoute="route('stock-in.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Masuk" />
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
                                <th class="p-2 text-left">ID Masuk</th>
                                <th class="p-2 text-left">ID Barang</th>
                                <th class="p-2 text-left">Nama Barang</th>
                                <th class="p-2 text-center">Jumlah</th>
                                <th class="p-2 text-right">Harga Modal</th>
                                <th class="p-2 text-right">Total</th>
                                <th class="p-2 text-left">Tanggal</th>
                                <th class="p-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockIns as $record)
                                <tr class="border-t hover:bg-surface-secondary">
                                    <td class="p-2 font-medium text-primary">{{ $record->id_stock_in }}</td>
                                    <td class="p-2">{{ $record->id_item }}</td>
                                    <td class="p-2">{{ $record->item->name_item ?? '-' }}</td>
                                    <td class="p-2 text-center">{{ $record->quantity }}</td>
                                    <td class="p-2 text-right">Rp {{ number_format($record->capital_price, 0, ',', '.') }}
                                    </td>
                                    <td class="p-2 text-right font-semibold">Rp
                                        {{ number_format($record->total_capital, 0, ',', '.') }}</td>
                                    <td class="p-2">{{ $record->tanggal->format('d M Y') }}</td>
                                    <td class="p-2 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button type="button"
                                                onclick="openModal('editModal-{{ $record->id_stock_in }}')"
                                                class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors text-xs">
                                                <i class="fa-solid fa-pen w-3 h-3"></i> Edit
                                            </button>
                                            <button type="button"
                                                onclick="deleteRecord('{{ route('stock-in.destroy', $record->id_stock_in) }}')"
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
        <x-pagination :paginator="$stockIns" />
    </div>

    {{-- Modal Tambah --}}
    @include('components.inventory.stock-in.add-modal', ['items' => $items])

    {{-- Modal Edit untuk setiap record --}}
    @foreach ($stockIns as $record)
        @include('components.inventory.stock-in.edit-modal', ['record' => $record])
    @endforeach

    {{-- Modal Konfirmasi Delete --}}
    <x-modal id="deleteConfirmModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data ini?
    </x-modal>

    {{-- Include Stock In Scripts --}}
    @include('partials.inventory.stock-in-scripts')
@endsection
