{{-- Tabel Data Pengembalian Barang (gaya Filament): toolbar (aksi massal +
     filter tipe/bulan/tahun + pencarian), tabel, paginasi.
     Jumlah baris terpilih dihitung Alpine untuk menampilkan grup aksi massal
     ("x data dipilih" + tombol hapus); status disabled #delete-button,
     #selectAll, dan auto-submit filter (#return_type, #month-select,
     #year-select) tetap diatur item-returns/index.js. --}}
@php
    $isFiltered = request()->filled('search') || request()->filled('return_type')
        || request()->filled('month') || request()->filled('year');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_returns]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_returns]:checked').length">

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between sm:px-6">
        {{-- Aksi massal: hanya tampil saat ada baris yang dicentang, agar tidak
             tertukar dengan hapus satu data lewat menu ⋮ --}}
        <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
            </span>

            {{-- Tombol Hapus Massal (status disabled tetap diatur item-returns/index.js) --}}
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form Filter & Pencarian: submit GET ke route('item-return.index').
             Tipe/bulan/tahun langsung submit saat diganti (item-returns/index.js);
             pencarian saat Enter. --}}
        <form method="GET" action="{{ route('item-return.index') }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center lg:ms-auto lg:w-auto lg:flex-nowrap">
            {{-- Tipe: 'masuk' = diretur ke supplier (stok berkurang),
                 'keluar' = kembali dari proyek/konsumen (stok bertambah).
                 Dipakai scope filterReturnType() pada ItemReturnController::baseQuery(). --}}
            <div class="col-span-2 sm:w-56">
                <x-fi.select name="return_type" id="return_type" aria-label="Pilih Tipe Pengembalian"
                    prefix-icon="arrow-uturn-left" placeholder="Semua Tipe" :value="request('return_type')"
                    :options="['masuk' => 'Retur Barang Masuk', 'keluar' => 'Retur Barang Keluar']" />
            </div>
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari ID / nama barang..."
                class="col-span-2 sm:w-56" />

            @if ($isFiltered)
                <x-fi.link :href="route('item-return.index')" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    {{-- Seluruh tabel dibungkus form #deleteForm → route('item-return.bulk-delete')
         (method DELETE) berisi checkbox selected_returns[] untuk hapus massal. --}}
    <form id="deleteForm" method="POST" action="{{ route('item-return.bulk-delete') }}">
        @csrf
        @method('DELETE')

        <div class="overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- Header Tabel --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-3 py-3.5 sm:ps-6">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th>ID Pengembalian</x-fi.table.th>
                        <x-fi.table.th>Tanggal</x-fi.table.th>
                        <x-fi.table.th>Tipe</x-fi.table.th>
                        <x-fi.table.th>Barang</x-fi.table.th>
                        {{-- Jumlah bertanda: + stok bertambah, − stok berkurang --}}
                        <x-fi.table.th align="end">
                            <span class="inline-flex items-center gap-1"
                                title="Pengaruh ke stok barang: + stok bertambah, − stok berkurang">
                                Jumlah
                                <x-fi.icon name="information-circle" class="h-4 w-4 text-gray-400" />
                            </span>
                        </x-fi.table.th>
                        <x-fi.table.th>Alasan</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse($returns as $record)
                        @php
                            $isMasuk = $record->return_type === 'masuk';
                        @endphp
                        <tr class="transition duration-75 hover:bg-gray-50 has-[:checked]:bg-primary-light/30">

                            {{-- Checkbox --}}
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="selected_returns[]"
                                    value="{{ $record->id_return }}"
                                    class="fi-checkbox" title="Pilih {{ $record->id_return }}">
                            </td>

                            {{-- ID Pengembalian --}}
                            <x-fi.table.td>
                                <x-fi.badge color="primary">{{ $record->id_return }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Tanggal --}}
                            <x-fi.table.td>{{ $record->date->format('d M Y') }}</x-fi.table.td>

                            {{-- Tipe + arah barang & transaksi sumber (Barang Masuk / Barang Keluar asal) --}}
                            <x-fi.table.td class="min-w-[13rem] whitespace-normal">
                                @if ($isMasuk)
                                    <x-fi.badge color="warning" icon="arrow-up-tray">Retur Barang Masuk</x-fi.badge>
                                    <div class="mt-1 text-sm text-gray-500" title="Sumber: Barang Masuk {{ $record->id_stock_in }}">
                                        Ke supplier · <span class="whitespace-nowrap">{{ $record->id_stock_in ?: '—' }}</span>
                                    </div>
                                @else
                                    <x-fi.badge color="success" icon="inbox-arrow-down">Retur Barang Keluar</x-fi.badge>
                                    <div class="mt-1 text-sm text-gray-500" title="Sumber: Barang Keluar {{ $record->id_stock_out }}">
                                        Dari proyek · <span class="whitespace-nowrap">{{ $record->id_stock_out ?: '—' }}</span>
                                    </div>
                                    @if ($record->stockOut?->project_name)
                                        <div class="text-sm text-gray-500">{{ $record->stockOut->project_name }}</div>
                                    @endif
                                @endif
                            </x-fi.table.td>

                            {{-- Barang: nama + ID barang --}}
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal">
                                <div class="font-medium">{{ $record->item->name_item ?? '-' }}</div>
                                <div class="text-sm text-gray-500">{{ $record->id_item }}</div>
                            </x-fi.table.td>

                            {{-- Jumlah bertanda: masuk (ke supplier) − stok, keluar (dari proyek) + stok --}}
                            <x-fi.table.td align="end" :class="'font-medium tabular-nums ' . ($isMasuk ? 'text-red-600' : 'text-emerald-600')"
                                title="{{ $isMasuk ? 'Stok berkurang' : 'Stok bertambah' }}">
                                {{ ($isMasuk ? '−' : '+') . number_format($record->quantity, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Alasan (+ keterangan; maks. 2 baris, teks lengkap di tooltip) --}}
                            <x-fi.table.td class="min-w-[10rem] max-w-xs whitespace-normal">
                                <div class="line-clamp-2" title="{{ $record->reason }}">{{ $record->reason ?: '—' }}</div>
                                @if ($record->notes)
                                    <div class="line-clamp-2 text-sm text-gray-500" title="{{ $record->notes }}">{{ $record->notes }}</div>
                                @endif
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ berisi menu aksi baris --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $record->id_return }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            onclick="openModal('editModal-{{ $record->id_return }}')">
                                            Edit Pengembalian
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    {{-- Aksi destruktif dipisah di paling bawah --}}
                                    <x-fi.dropdown.list>
                                        {{-- Hapus data ini saja → route('item-return.destroy') (modal x-fi.delete-row-modal) --}}
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            data-delete-row="{{ $record->id_return }}"
                                            data-delete-row-action="{{ route('item-return.destroy', $record->id_return) }}"
                                            data-delete-row-label="{{ $record->id_return }} · {{ $record->item->name_item ?? $record->id_item }} ({{ number_format($record->quantity, 0, ',', '.') }} unit)">
                                            Hapus Pengembalian
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        {{-- Pesan jika data kosong --}}
                        <tr>
                            <td colspan="8">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada pengembalian barang yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="route('item-return.index')">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="arrow-uturn-left" heading="Belum ada pengembalian barang"
                                        description="Klik “Tambah Pengembalian” untuk mencatat retur barang ke supplier atau dari proyek." />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Paginasi (selalu tampil, termasuk saat hanya 1 halaman) --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$returns" />
    </div>
</x-fi.card>
