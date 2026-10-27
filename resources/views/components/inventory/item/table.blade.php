{{-- Tabel Data Barang (gaya Filament): toolbar (hapus massal + pencarian), tabel, paginasi.
     Jumlah baris terpilih dihitung Alpine hanya untuk tampilan "x data dipilih";
     status tombol #delete-button tetap diatur items/index.js. --}}
<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_items]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_items]:checked').length">

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-3">
            {{-- Tombol Hapus Massal (aktif bila ada baris yang dicentang) --}}
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>

            <span x-cloak x-show="selected > 0" class="text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
            </span>
        </div>

        {{-- Form Pencarian: submit GET ke route('item.index') dengan param 'search' (Enter). --}}
        <form method="GET" action="{{ route('item.index') }}" class="w-full sm:max-w-xs">
            <x-fi.search-input :value="request('search')" placeholder="Cari barang..." />
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('items.destroySelected') }}">
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
                        <x-fi.table.th>ID Barang</x-fi.table.th>
                        <x-fi.table.th>Nama Barang</x-fi.table.th>
                        <x-fi.table.th align="center">Jumlah</x-fi.table.th>
                        <x-fi.table.th align="end">Harga Modal</x-fi.table.th>
                        <x-fi.table.th align="end">Harga Jual</x-fi.table.th>
                        <x-fi.table.th>Keterangan</x-fi.table.th>
                        <x-fi.table.th align="end"><span class="sr-only">Aksi</span></x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse($items as $item)
                        <tr class="transition duration-75 hover:bg-gray-50 has-[:checked]:bg-primary-light/30">

                            {{-- Checkbox --}}
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="selected_items[]" value="{{ $item->id_item }}"
                                    class="fi-checkbox" title="Pilih {{ $item->name_item }}">
                            </td>

                            {{-- ID Barang --}}
                            <x-fi.table.td>
                                <x-fi.badge color="primary">{{ $item->id_item }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Nama Barang --}}
                            <x-fi.table.td class="font-medium">{{ $item->name_item }}</x-fi.table.td>

                            {{-- Jumlah Stok (merah bila habis) --}}
                            <x-fi.table.td align="center">
                                <x-fi.badge :color="$item->quantity > 0 ? 'gray' : 'danger'">{{ $item->quantity }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Harga Modal --}}
                            <x-fi.table.td align="end" class="tabular-nums text-gray-700">
                                {{ 'Rp ' . number_format($item->capital_price, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Harga Jual --}}
                            <x-fi.table.td align="end" class="font-medium tabular-nums">
                                {{ 'Rp ' . number_format($item->selling_price, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Keterangan --}}
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal text-gray-500">
                                {{ $item->keterangan ?: '—' }}
                            </x-fi.table.td>

                            {{-- Tombol Aksi --}}
                            <x-fi.table.td align="end">
                                <x-fi.link icon="pencil-square" onclick="openModal('editModal-{{ $item->id_item }}')"
                                    title="Edit Barang">
                                    Edit
                                </x-fi.link>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        {{-- Pesan jika data kosong --}}
                        <tr>
                            <td colspan="8">
                                @if (request('search'))
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        :description="'Tidak ada barang yang cocok dengan pencarian “' . request('search') . '”.'" />
                                @else
                                    <x-fi.empty-state icon="cube" heading="Belum ada data barang"
                                        description="Klik “Tambah Barang” untuk menambahkan data pertama." />
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
        <x-fi.pagination :paginator="$items" />
    </div>
</x-fi.card>
