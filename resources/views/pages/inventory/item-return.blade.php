{{-- =====================================================================
     Halaman: Pengembalian Barang (Item Return)
     Tujuan: Menampilkan daftar return barang (tipe masuk & keluar) dengan
             filter tipe/bulan/tahun + pencarian, serta CRUD return
             (tambah/edit/hapus massal) dan export PDF/Excel.
     Data dari ItemReturnController@index:
     - $returns      : LengthAwarePaginator hasil baseQuery (search, return_type,
                       month, year) + eager load item, stockOut, stockIn.
     - $items        : seluruh master barang (ItemService::getAll()).
     - $stockOuts    : seluruh catatan barang keluar (StockService::getAllStockOuts()).
     - $stockIns     : seluruh catatan barang masuk (StockInService::getAllStockIns()).
     - $maxQuantities: array [id_return => max qty return] hasil computeMaxQuantities()
                       untuk validasi di modal edit.
     Komponen yang di-include:
     - components.inventory.item-return.add-modal  (dengan $items, $stockOuts, $stockIns)
     - components.inventory.item-return.edit-modal (per record + $maxQuantities)
     - x-filters.*, x-buttons.*, x-pagination, x-modal
     JS: inline window.ITEM_RETURN_DATA + @vite('resources/js/pages/inventory/item-returns/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Pengembalian Barang')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Pengembalian Barang</h1>

        {{-- SECTION: Filter, Pencarian & Toolbar Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Filter & Pencarian --}}
            <form method="GET" action="{{ route('item-return.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">

                {{-- Filter Tipe Return: 'masuk' = barang kembali ke supplier (stok berkurang),
                     'keluar' = barang kembali dari proyek/konsumen (stok bertambah).
                     Nilai dipakai scope filterReturnType() pada ItemReturnController::baseQuery(). --}}
                <div class="w-full min-[1530px]:w-auto">
                    <select name="return_type" id="return_type"
                        class="block w-full min-[1530px]:w-48 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light transition-colors duration-150">
                        <option value="">Semua Tipe</option>
                        <option value="masuk" @selected(request('return_type') === 'masuk')>Pengembalian Masuk</option>
                        <option value="keluar" @selected(request('return_type') === 'keluar')>Pengembalian Keluar</option>
                    </select>
                </div>

                {{-- Filter Bulan: value dari request('month'); dipakai scope filterMonth() --}}
                <x-filters.month-filter :value="request('month')" responsive="custom" />

                {{-- Filter Tahun: value dari request('year'); dipakai scope filterYear() --}}
                <x-filters.year-filter :value="request('year')" responsive="custom" />

                {{-- Input Pencarian: kata kunci via request('search') (scope search di model) --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari return barang..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    {{-- Dropdown Export (PDF & Excel) --}}
                    {{-- queryParams meneruskan filter aktif (search, month, year, return_type)
                         agar hasil export sesuai daftar yang sedang ditampilkan. --}}
                    <x-buttons.print-dropdown
                        :excelRoute="route('item-return.export.excel')"
                        :pdfRoute="route('item-return.export.pdf')"
                        :queryParams="[
                            'search' => request('search'),
                            'month' => request('month'),
                            'year' => request('year'),
                            'return_type' => request('return_type'),
                        ]"
                        responsive="custom" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                    {{-- Tombol Tambah Pengembalian --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Pengembalian" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- SECTION: Tabel Data Pengembalian --}}
        {{-- Seluruh tabel dibungkus form #deleteForm yang dikirim ke route('item-return.bulk-delete')
             (method DELETE) berisi checkbox selected_returns[] untuk hapus massal. --}}
        <form id="deleteForm" method="POST" action="{{ route('item-return.bulk-delete') }}">
            @csrf
            @method('DELETE')

            {{-- Tabel Utama --}}
            <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="inline-block min-w-full align-middle">
                    <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">

                            {{-- Header Tabel --}}
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
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

                            {{-- Body Tabel: iterasi $returns; tiap baris menampilkan record return.
                                 Nama barang via relasi $record->item, tanggal diformat 'd M Y'. --}}
                            <tbody>
                                @forelse($returns as $record)
                                    <tr class="border-t hover:bg-surface-secondary">

                                        {{-- Checkbox --}}
                                        <td class="p-2 text-center">
                                            <input type="checkbox" name="selected_returns[]"
                                                value="{{ $record->id_return }}"
                                                class="w-4 h-4 accent-primary cursor-pointer">
                                        </td>

                                        {{-- ID Return --}}
                                        <td class="p-2 font-medium text-primary">{{ $record->id_return }}</td>

                                        {{-- ID Barang --}}
                                        <td class="p-2">{{ $record->id_item }}</td>

                                        {{-- Nama Barang --}}
                                        <td class="p-2">{{ $record->item->name_item ?? '-' }}</td>

                                        {{-- Jumlah --}}
                                        <td class="p-2 text-center">{{ $record->quantity }}</td>

                                        {{-- Alasan --}}
                                        <td class="p-2 text-sm">{{ $record->reason ?? '-' }}</td>

                                        {{-- Tipe: badge label 'Masuk'/'Keluar' sesuai nilai $record->return_type --}}
                                        <td class="p-2 text-center">
                                            @if ($record->return_type === 'masuk')
                                                <span class="bg-surface-secondary text-text-primary px-2 py-1 rounded text-xs font-medium">Masuk</span>
                                            @else
                                                <span class="bg-surface-secondary text-text-primary px-2 py-1 rounded text-xs font-medium">Keluar</span>
                                            @endif
                                        </td>

                                        {{-- Tanggal --}}
                                        <td class="p-2">{{ $record->date->format('d M Y') }}</td>

                                        {{-- Tombol Aksi --}}
                                        <td class="p-2 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button type="button"
                                                    onclick="openModal('editModal-{{ $record->id_return }}')"
                                                    class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors text-xs">
                                                    <i class="fa-solid fa-pen w-3 h-3"></i> Edit
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    {{-- Pesan jika data kosong --}}
                                    <tr>
                                        <td colspan="9" class="text-center p-4 text-text-secondary">
                                            Data tidak ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

    </div>

    {{-- SECTION: Pagination --}}
    {{-- Paginasi data $returns (LengthAwarePaginator, 15 per halaman) --}}
    <x-pagination :paginator="$returns" />

    {{-- SECTION: Modal Tambah Pengembalian --}}
    {{-- Alur form modal (ditangani JS item-returns/index.js):
         1) Pilih Tipe Return (masuk/keluar) — menentukan sumber stok.
         2) Pilih Barang → dropdown stok sumber relevan difilter dari window.ITEM_RETURN_DATA
            ($stockIns untuk tipe masuk, $stockOuts untuk tipe keluar).
         3) Validasi quantity: client-side (#addQuantityWarning) dan server-side
            (ItemReturnService::createReturn() melempar RuntimeException bila melebihi stok). --}}
    @include('components.inventory.item-return.add-modal', [
        'items' => $items,
        'stockOuts' => $stockOuts,
        'stockIns' => $stockIns,
    ])

    {{-- SECTION: Modal Edit Pengembalian (satu modal per record) --}}
    {{-- maxQuantity dihitung pre-compute di controller (computeMaxQuantities) untuk validasi
         jumlah return saat edit: sisa stok sumber + quantity return lama. --}}
    @foreach ($returns as $record)
        @include('components.inventory.item-return.edit-modal', [
            'record' => $record,
            'stockOuts' => $stockOuts,
            'stockIns' => $stockIns,
            'maxQuantity' => $maxQuantities[$record->id_return] ?? 0,
        ])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    {{-- Konfirmasi memanggil submitDeleteForm() → submit form #deleteForm
         (route('item-return.bulk-delete')). --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- SECTION: Scripts (Data untuk JavaScript) --}}
    {{-- window.ITEM_RETURN_DATA menyuplai data stok sumber (stockIns/stockOuts) & master item
         ke modul item-returns/index.js untuk logika dropdown & validasi quantity client-side. --}}
    @push('scripts')
        <script>
            window.ITEM_RETURN_DATA = {
                stockIns: {!! json_encode($stockIns->map(fn($s) => [
                    'id_stock_in' => $s->id_stock_in,
                    'id_item' => $s->id_item,
                    'quantity' => $s->quantity,
                ])) !!},
                stockOuts: {!! json_encode($stockOuts->map(fn($s) => [
                    'id_stock_out' => $s->id_stock_out,
                    'id_item' => $s->id_item,
                    'quantity' => $s->quantity,
                ])) !!},
                items: {!! json_encode($items->map(fn($i) => [
                    'id_item' => $i->id_item,
                    'name_item' => $i->name_item,
                ])) !!}
            };
        </script>

        @vite('resources/js/pages/inventory/item-returns/index.js')
    @endpush
@endsection
