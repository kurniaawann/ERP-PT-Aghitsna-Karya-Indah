{{-- Tabel Data Barang Keluar (gaya Filament, read-only): toolbar (filter
     bulan/tahun + pencarian), tabel, paginasi. Tanpa checkbox/kolom aksi
     karena data dibuat otomatis dari Rekap Penjualan. --}}
@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year');
@endphp

<x-fi.card>

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-end sm:px-6">
        {{-- Form Filter & Pencarian: submit GET ke route('stock-out.index').
             Bulan/tahun langsung submit saat diganti; pencarian saat Enter. --}}
        <form method="GET" action="{{ route('stock-out.index') }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:items-center lg:w-auto">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari ID / nama barang..."
                class="col-span-2 sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="route('stock-out.index')" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full table-auto divide-y divide-gray-200 text-start">

            {{-- Header Tabel --}}
            <thead class="bg-gray-50">
                <tr>
                    <x-fi.table.th>ID Barang Keluar</x-fi.table.th>
                    <x-fi.table.th>Tanggal</x-fi.table.th>
                    <x-fi.table.th>Barang</x-fi.table.th>
                    <x-fi.table.th align="end">Jumlah Keluar</x-fi.table.th>
                    {{-- remaining_quantity = stok barang saat ini (bukan sisa setelah transaksi ini) --}}
                    <x-fi.table.th align="center">
                        <span class="inline-flex items-center gap-1"
                            title="Stok barang saat ini di Data Barang (bukan sisa setelah transaksi ini)">
                            Stok Saat Ini
                            <x-fi.icon name="information-circle" class="h-4 w-4 text-gray-400" />
                        </span>
                    </x-fi.table.th>
                    <x-fi.table.th>Proyek</x-fi.table.th>
                </tr>
            </thead>

            {{-- Body Tabel --}}
            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                @forelse($stockOuts as $record)
                    <tr class="transition duration-75 hover:bg-gray-50">

                        {{-- ID Barang Keluar --}}
                        <x-fi.table.td>
                            <x-fi.badge color="primary">{{ $record->id_stock_out }}</x-fi.badge>
                        </x-fi.table.td>

                        {{-- Tanggal --}}
                        <x-fi.table.td>{{ $record->date->format('d M Y') }}</x-fi.table.td>

                        {{-- Barang: nama + ID barang --}}
                        <x-fi.table.td class="min-w-[12rem] whitespace-normal">
                            <div class="font-medium">{{ $record->item->name_item ?? '-' }}</div>
                            <div class="text-sm text-gray-500">{{ $record->id_item }}</div>
                        </x-fi.table.td>

                        {{-- Jumlah Keluar (+ jumlah yang sudah dikembalikan, bila ada) --}}
                        <x-fi.table.td align="end" class="tabular-nums">
                            <div class="font-medium">{{ number_format($record->quantity, 0, ',', '.') }}</div>
                            @if ($record->total_returned > 0)
                                <div class="mt-0.5 inline-flex items-center gap-1 text-sm text-amber-600"
                                    title="Jumlah barang dari transaksi ini yang sudah dikembalikan (Pengembalian Barang)">
                                    <x-fi.icon name="arrow-uturn-left" class="h-4 w-4" />
                                    {{ number_format($record->total_returned, 0, ',', '.') }} dikembalikan
                                </div>
                            @endif
                        </x-fi.table.td>

                        {{-- Stok Saat Ini (merah bila habis) --}}
                        <x-fi.table.td align="center">
                            @if ($record->item)
                                <x-fi.badge :color="$record->remaining_quantity > 0 ? 'gray' : 'danger'">
                                    {{ number_format($record->remaining_quantity, 0, ',', '.') }}
                                </x-fi.badge>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </x-fi.table.td>

                        {{-- Proyek + sumber (Rekap Penjualan) --}}
                        <x-fi.table.td class="min-w-[12rem] whitespace-normal">
                            <div>{{ $record->project_name ?: '-' }}</div>
                            @if ($record->id_sales_recap)
                                <div class="text-sm text-gray-500">Rekap Penjualan {{ $record->id_sales_recap }}</div>
                            @endif
                        </x-fi.table.td>
                    </tr>
                @empty
                    {{-- Pesan jika data kosong --}}
                    <tr>
                        <td colspan="6">
                            @if ($isFiltered)
                                <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                    description="Tidak ada barang keluar yang cocok dengan filter/pencarian.">
                                    <x-fi.button color="gray" icon="x-mark" :href="route('stock-out.index')">Reset filter</x-fi.button>
                                </x-fi.empty-state>
                            @else
                                <x-fi.empty-state icon="arrow-up-tray" heading="Belum ada barang keluar"
                                    description="Barang keluar tercatat otomatis dari Rekap Penjualan yang memakai barang dari stok." />
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginasi (selalu tampil, termasuk saat hanya 1 halaman) --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$stockOuts" />
    </div>
</x-fi.card>
