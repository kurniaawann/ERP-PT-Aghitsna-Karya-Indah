{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN TABEL DIVISI (gaya Filament)
     Toolbar (hapus massal kiri, pencarian kanan) + tabel + paginasi dalam
     satu kartu. Jumlah karyawan memakai employees_count (withCount) agar
     tidak N+1; divisi yang masih punya karyawan tidak bisa dihapus (item
     Hapus di menu ⋮ dinonaktifkan, sama dengan aturan di controller).

     Hook JS (resources/js/pages/sdm/division/index.js): #selectAll,
     input[name="ids[]"], #delete-button, form#deleteForm.
     Parameter: $divisions (paginator Division)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    $indexUrl = route('division.index');
    $isFiltered = request()->filled('search');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=ids]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=ids]:checked').length">

    {{-- ─── Toolbar: aksi massal (kiri) + pencarian (kanan) ──────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex flex-wrap items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
            </span>
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        <form method="GET" action="{{ $indexUrl }}"
            class="flex w-full flex-wrap items-center gap-3 sm:ms-auto sm:w-auto sm:justify-end">
            <x-fi.search-input :value="request('search')" placeholder="Cari divisi..." class="w-full sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark" class="whitespace-nowrap" title="Hapus pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    {{-- ─── Form Hapus Massal + Tabel ────────────────────────────────────────── --}}
    <form id="deleteForm" method="POST" action="{{ route('division.destroy') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-3 py-3.5 sm:ps-6">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th>Nama Divisi</x-fi.table.th>
                        <x-fi.table.th>Deskripsi</x-fi.table.th>
                        <x-fi.table.th align="center">Jumlah Karyawan</x-fi.table.th>
                        <x-fi.table.th align="center" class="w-1">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($divisions as $division)
                        @php $inUse = $division->employees_count > 0; @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="ids[]" value="{{ $division->id }}" class="fi-checkbox"
                                    title="Pilih {{ $division->name }}">
                            </td>
                            <x-fi.table.td class="font-medium">{{ $division->name }}</x-fi.table.td>
                            <x-fi.table.td class="min-w-[14rem] whitespace-normal text-gray-700">{{ $division->description ?? '-' }}</x-fi.table.td>
                            <x-fi.table.td align="center">
                                <x-fi.badge :color="$inUse ? 'primary' : 'gray'" icon="user-group">{{ $division->employees_count }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $division->name }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            onclick="openModal('editModal-{{ $division->id }}')">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            :data-delete-row="$inUse ? null : $division->id" :data-delete-row-label="$division->name"
                                            :disabled="$inUse" :hint="$inUse ? 'Masih memiliki ' . $division->employees_count . ' karyawan' : null">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada divisi yang cocok dengan pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset pencarian</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="rectangle-group" heading="Data tidak ditemukan" />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- ─── Paginasi ─────────────────────────────────────────────────────────── --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$divisions" />
    </div>
</x-fi.card>
