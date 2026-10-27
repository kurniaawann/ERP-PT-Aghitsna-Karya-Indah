{{-- Tabel Data Barang Masuk (gaya Filament): toolbar (aksi massal + filter
     bulan/tahun + pencarian), tabel, paginasi.
     Jumlah baris terpilih dihitung Alpine untuk menampilkan grup aksi massal
     ("x data dipilih" + tombol hapus); status disabled #delete-button tetap
     diatur incoming-items/index.js. --}}
@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_stock_ins]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_stock_ins]:checked').length">

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between sm:px-6">
        {{-- Aksi massal: hanya tampil saat ada baris yang dicentang, agar tidak
             tertukar dengan hapus satu data lewat menu ⋮ --}}
        <div x-cloak x-show="selected > 0" class="flex items-center gap-3">
            <span class="text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
            </span>

            {{-- Tombol Hapus Massal (status disabled tetap diatur incoming-items/index.js) --}}
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form Filter & Pencarian: submit GET ke route('stock-in.index').
             Bulan/tahun langsung submit saat diganti; pencarian saat Enter. --}}
        <form method="GET" action="{{ route('stock-in.index') }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:items-center lg:ms-auto lg:w-auto">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari ID / nama barang..."
                class="col-span-2 sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="route('stock-in.index')" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('stock-ins.destroySelected') }}">
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
                        <x-fi.table.th>ID Barang Masuk</x-fi.table.th>
                        <x-fi.table.th>Tanggal</x-fi.table.th>
                        <x-fi.table.th>Barang</x-fi.table.th>
                        <x-fi.table.th align="end">Jumlah</x-fi.table.th>
                        <x-fi.table.th align="end">Harga Modal</x-fi.table.th>
                        <x-fi.table.th align="end">Total</x-fi.table.th>
                        <x-fi.table.th>Keterangan</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse($stockIns as $record)
                        <tr class="transition duration-75 hover:bg-gray-50 has-[:checked]:bg-primary-light/30">

                            {{-- Checkbox --}}
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="selected_stock_ins[]"
                                    value="{{ $record->id_stock_in }}"
                                    class="fi-checkbox" title="Pilih {{ $record->id_stock_in }}">
                            </td>

                            {{-- ID Barang Masuk --}}
                            <x-fi.table.td>
                                <x-fi.badge color="primary">{{ $record->id_stock_in }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Tanggal --}}
                            <x-fi.table.td>{{ $record->date->format('d M Y') }}</x-fi.table.td>

                            {{-- Barang: nama + ID barang --}}
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal">
                                <div class="font-medium">{{ $record->item->name_item ?? '-' }}</div>
                                <div class="text-sm text-gray-500">{{ $record->id_item }}</div>
                            </x-fi.table.td>

                            {{-- Jumlah --}}
                            <x-fi.table.td align="end" class="tabular-nums">
                                {{ number_format($record->quantity, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Harga Modal (per unit) --}}
                            <x-fi.table.td align="end" class="tabular-nums text-gray-700">
                                {{ 'Rp ' . number_format($record->capital_price, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Total Harga Modal --}}
                            <x-fi.table.td align="end" class="font-medium tabular-nums">
                                {{ 'Rp ' . number_format($record->total_capital, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Keterangan (maks. 2 baris, teks lengkap di tooltip) --}}
                            <x-fi.table.td class="min-w-[12rem] max-w-xs whitespace-normal text-gray-500">
                                <span class="line-clamp-2" title="{{ $record->notes }}">{{ $record->notes ?: '—' }}</span>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ berisi menu aksi baris --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $record->id_stock_in }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            onclick="openModal('editModal-{{ $record->id_stock_in }}')">
                                            Edit Barang Masuk
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    {{-- Aksi destruktif dipisah di paling bawah --}}
                                    <x-fi.dropdown.list>
                                        {{-- Hapus data ini saja → route('stock-in.destroy') (modal x-fi.delete-row-modal) --}}
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            data-delete-row="{{ $record->id_stock_in }}"
                                            data-delete-row-action="{{ route('stock-in.destroy', $record->id_stock_in) }}"
                                            data-delete-row-label="{{ $record->id_stock_in }} · {{ $record->item->name_item ?? $record->id_item }} ({{ number_format($record->quantity, 0, ',', '.') }} unit)">
                                            Hapus Barang Masuk
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        {{-- Pesan jika data kosong --}}
                        <tr>
                            <td colspan="9">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada barang masuk yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="route('stock-in.index')">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="inbox-arrow-down" heading="Belum ada barang masuk"
                                        description="Klik “Tambah Barang Masuk” untuk mencatat penerimaan barang pertama." />
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
        <x-fi.pagination :paginator="$stockIns" />
    </div>
</x-fi.card>
